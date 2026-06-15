import logging
import os
import time

import undetected_chromedriver
from selenium.common.exceptions import WebDriverException

from services.yandex_reviews_parser.exceptions import DriverError, ParserError
from services.yandex_reviews_parser.parsers import Parser

logger = logging.getLogger(__name__)

PAGE_LOAD_DELAY = int(os.environ.get("PARSER_PAGE_DELAY", "4"))


class YandexParser:
    def __init__(self, id_yandex: int):
        """
        @param id_yandex: ID Яндекс компании
        """
        self.id_yandex = id_yandex

    def __build_driver(self):
        opts = undetected_chromedriver.ChromeOptions()
        opts.add_argument('--no-sandbox')
        opts.add_argument('--disable-dev-shm-usage')
        opts.add_argument('--headless=new')
        opts.add_argument('--disable-gpu')
        chrome_bin = os.environ.get('CHROME_BIN')
        if chrome_bin:
            opts.binary_location = chrome_bin
        return undetected_chromedriver.Chrome(options=opts)

    def parse(self, type_parse: str = 'default') -> dict:
        """
        Получить данные организации.

        @param type_parse: default — компания и отзывы, company — только компания,
            reviews — только отзывы.
        @return: данные либо {'error': '...'} с понятным сообщением об ошибке.
        """
        url = f'https://yandex.ru/maps/org/{self.id_yandex}/reviews/'

        try:
            driver = self.__build_driver()
        except WebDriverException:
            logger.exception("Не удалось запустить браузер для %s", self.id_yandex)
            return {'error': DriverError().message}

        try:
            driver.get(url)
            time.sleep(PAGE_LOAD_DELAY)
            page = Parser(driver)

            if type_parse == 'company':
                return page.parse_company_info()
            if type_parse == 'reviews':
                return page.parse_reviews()
            return page.parse_all_data()
        except ParserError as exc:
            logger.warning("Парсинг %s не удался: %s", self.id_yandex, exc.message)
            return {'error': exc.message}
        except WebDriverException:
            logger.exception("Ошибка драйвера при парсинге %s", self.id_yandex)
            return {'error': DriverError().message}
        except Exception:  # noqa: BLE001 — не даём упасть в 500, отдаём понятную ошибку
            logger.exception("Непредвиденная ошибка при парсинге %s", self.id_yandex)
            return {'error': ParserError().message}
        finally:
            try:
                driver.quit()
            except Exception:  # noqa: BLE001 — закрытие драйвера не должно ломать ответ
                logger.debug("Не удалось корректно закрыть драйвер", exc_info=True)
