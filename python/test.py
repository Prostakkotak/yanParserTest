import json
import os

import certifi

# python.org macOS builds ship without a CA bundle; undetected_chromedriver needs HTTPS
os.environ.setdefault("SSL_CERT_FILE", certifi.where())
os.environ.setdefault("REQUESTS_CA_BUNDLE", certifi.where())

from services.yandex_reviews_parser import YandexParser

id_ya = 4832448753  # ID Компании Yandex
output_file = f"data_{id_ya}.json"

parser = YandexParser(id_ya)
data = parser.parse()  # default — company_info + company_reviews

if "error" in data:
    print(data["error"])
else:
    with open(output_file, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    reviews_count = len(data.get("company_reviews", []))
    company_name = data.get("company_info", {}).get("name", "—")
    print(f"Saved to {output_file}: {company_name}, {reviews_count} reviews")
