import time
from dataclasses import asdict

from selenium.webdriver.common.by import By
from selenium.common.exceptions import NoSuchElementException

from services.yandex_reviews_parser.exceptions import MarkupChangedError, PageUnavailableError
from services.yandex_reviews_parser.helpers import ParserHelper
from services.yandex_reviews_parser.storage import Review, Info

REVIEW_CARD_CLASS = "business-reviews-card-view__review"

# Несколько кандидатов на контейнер списка отзывов: Яндекс периодически
# переименовывает классы, поэтому отсутствие всех сразу трактуем как смену вёрстки.
REVIEWS_CONTAINER_SELECTORS = (
    ".business-reviews-card-view",
    ".business-review-view",
    "[class*='reviews-card-view']",
)


class Parser:
    def __init__(self, driver):
        self.driver = driver

    def __scroll_to_bottom(self, elem) -> None:
        """
        Скроллим список до последнего отзыва
        :param elem: Последний отзыв в списке
        :param driver: Драйвер undetected_chromedriver
        :return: None
        """
        self.driver.execute_script(
            "arguments[0].scrollIntoView();",
            elem
        )
        time.sleep(1)
        new_elem = self.driver.find_elements(By.CLASS_NAME, REVIEW_CARD_CLASS)[-1]
        if elem == new_elem:
            return
        self.__scroll_to_bottom(new_elem)

    def __expand_review_texts(self) -> None:
        """Развернуть обрезанные тексты отзывов («Ещё»)."""
        for _ in range(3):
            expand_buttons = self.driver.find_elements(
                By.CSS_SELECTOR,
                '.business-review-view__expand[aria-hidden="false"]',
            )
            if not expand_buttons:
                break
            for button in expand_buttons:
                try:
                    self.driver.execute_script("arguments[0].click()", button)
                except Exception:
                    pass
            time.sleep(0.5)

    def __get_review_text(self, elem) -> str | None:
        text_selectors = (
            ".//span[contains(@class, 'spoiler-view__text-container')]",
            ".//div[contains(@class, 'business-review-view__body')]",
            ".//span[@itemprop='reviewBody']",
            ".//span[contains(@class, 'business-review-view__body-text')]",
        )
        for xpath in text_selectors:
            try:
                text_el = elem.find_element(By.XPATH, xpath)
                text = text_el.text.strip() if text_el.text else None
                if text:
                    return text
            except NoSuchElementException:
                continue
        return None

    def __get_review_stars(self, elem) -> float:
        try:
            rating_el = elem.find_element(By.XPATH, ".//meta[@itemprop='ratingValue']")
            return float(rating_el.get_attribute('content'))
        except (NoSuchElementException, TypeError, ValueError):
            pass
        try:
            stars = elem.find_elements(
                By.XPATH,
                ".//div[contains(@class, 'business-rating-badge-view__stars')]/span",
            )
            return ParserHelper.get_count_star(stars)
        except NoSuchElementException:
            return 0

    def __get_data_item(self, elem):
        """
        Спарсить данные по отзыву
        :param elem: Отзыв из списка
        :return: Словарь
        {
            name: str
            icon_href: Union[str, None]
            date: float
            text: str
            stars: float
        }
        """
        try:
            name = elem.find_element(By.XPATH, ".//span[@itemprop='name']").text
        except NoSuchElementException:
            name = None

        try:
            icon_href = elem.find_element(By.XPATH, ".//div[@class='user-icon-view__icon']").get_attribute('style')
            icon_href = icon_href.split('"')[1]
        except NoSuchElementException:
            icon_href = None

        try:
            date = elem.find_element(By.XPATH, ".//meta[@itemprop='datePublished']").get_attribute('content')
        except NoSuchElementException:
            date = None

        text = self.__get_review_text(elem)
        stars = self.__get_review_stars(elem)

        try:
            answer = elem.find_element(By.CLASS_NAME, "business-review-view__comment-expand")
            if answer:
                self.driver.execute_script("arguments[0].click()", answer)
                answer = elem.find_element(By.CLASS_NAME, "business-review-comment-content__bubble").text
            else:
                answer = None
        except NoSuchElementException:
            answer = None
        item = Review(
            name=name,
            icon_href=icon_href,
            date=ParserHelper.form_date(date) if date else None,
            text=text,
            stars=stars,
            answer=answer
        )
        return asdict(item)

    def __get_data_campaign(self) -> dict:
        """
        Получаем данные по компании.
        :return: Словарь данных
        {
            name: str
            rating: float
            count_rating: int
            stars: float
        }
        """
        try:
            xpath_name = ".//h1[@class='orgpage-header-view__header']"
            name = self.driver.find_element(By.XPATH, xpath_name).text
        except NoSuchElementException:
            name = None
        try:
            xpath_rating_block = ".//div[@class='business-summary-rating-badge-view__rating-and-stars']"
            rating_block = self.driver.find_element(By.XPATH, xpath_rating_block)
            xpath_rating = ".//div[@class='business-summary-rating-badge-view__rating']/span[contains(@class, 'business-summary-rating-badge-view__rating-text')]"
            rating = rating_block.find_elements(By.XPATH, xpath_rating)
            rating = ParserHelper.format_rating(rating)
            xpath_count_rating = ".//div[@class='business-summary-rating-badge-view__rating-count']/span[@class='business-rating-amount-view _summary']"
            count_rating_list = rating_block.find_element(By.XPATH, xpath_count_rating).text
            count_rating = ParserHelper.list_to_num(count_rating_list)
            xpath_stars = ".//div[@class='business-rating-badge-view__stars']/span"
            stars = ParserHelper.get_count_star(rating_block.find_elements(By.XPATH, xpath_stars))
        except NoSuchElementException:
            rating = 0
            count_rating = 0
            stars = 0

        item = Info(
            name=name,
            rating=rating,
            count_rating=count_rating,
            stars=stars
        )
        return asdict(item)

    def __get_data_reviews(self) -> list:
        elements = self.driver.find_elements(By.CLASS_NAME, REVIEW_CARD_CLASS)

        if not elements:
            # Карточек нет. Если на странице отсутствует и сам контейнер списка —
            # значит изменились классы вёрстки, а не «у организации нет отзывов».
            if not self.__reviews_container_present():
                raise MarkupChangedError()
            return []

        reviews = []
        self.__scroll_to_bottom(elements[-1])
        self.__expand_review_texts()
        elements = self.driver.find_elements(By.CLASS_NAME, REVIEW_CARD_CLASS)
        for elem in elements:
            reviews.append(self.__get_data_item(elem))
        return reviews

    def __reviews_container_present(self) -> bool:
        for selector in REVIEWS_CONTAINER_SELECTORS:
            if self.driver.find_elements(By.CSS_SELECTOR, selector):
                return True
        return False

    def __is_page_loaded(self) -> bool:
        try:
            self.driver.find_element(By.XPATH, ".//h1[@class='orgpage-header-view__header']")
            return True
        except NoSuchElementException:
            return False

    def parse_all_data(self) -> dict:
        """
        Начинаем парсить данные.
        :return: Словарь данных
        {
             company_info:{
                    name: str
                    rating: float
                    count_rating: int
                    stars: float
            },
            company_reviews:[
                {
                  name: str
                  icon_href: str
                  date: timestamp
                  text: str
                  stars: float
                }
            ]
        }
        """
        if not self.__is_page_loaded():
            raise PageUnavailableError()
        return {'company_info': self.__get_data_campaign(), 'company_reviews': self.__get_data_reviews()}

    def parse_reviews(self) -> dict:
        """
        Начинаем парсить данные только отзывы.
        :return: Массив отзывов
        {
            company_reviews:[
                {
                  name: str
                  icon_href: str
                  date: timestamp
                  text: str
                  stars: float
                }
            ]
        }

        """
        if not self.__is_page_loaded():
            raise PageUnavailableError()
        return {'company_reviews': self.__get_data_reviews()}

    def parse_company_info(self) -> dict:
        """
        Начинаем парсить данные только данные о компании.
        :return: Объект компании
        {
            company_info:
                {
                    name: str
                    rating: float
                    count_rating: int
                    stars: float
                }
        }
        """
        if not self.__is_page_loaded():
            raise PageUnavailableError()
        return {'company_info': self.__get_data_campaign()}
