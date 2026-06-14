import os

import certifi
from flask import Flask, jsonify

os.environ.setdefault("SSL_CERT_FILE", certifi.where())
os.environ.setdefault("REQUESTS_CA_BUNDLE", certifi.where())

from services.yandex_reviews_parser import YandexParser

app = Flask(__name__)


@app.get("/health")
def health():
    return jsonify({"status": "ok"})


@app.get("/parse/<int:org_id>")
def parse_organization(org_id: int):
    parser = YandexParser(org_id)
    data = parser.parse()

    if "error" in data:
        return jsonify({"error": data["error"]}), 422

    return jsonify(data)


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=int(os.environ.get("PORT", "8080")))
