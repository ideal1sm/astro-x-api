# Документация по API

## Получение товара по id
#### URl: http://localhost:8080/api/v1/products/{id}
#### METHOD: GET

#### Пример успешного ответа
```json
{
    "code": "SUCCESS",
    "data": {
        "id": 1,
        "color": "серебряный",
        "composition": "хуевый",
        "price": "11111111.00",
        "inlay": "пидор",
        "lock_type": "английский пидор",
        "length": "110",
        "production": "Уганда",
        "brand": "Азамат",
        "zodiac_sign": "aries",
        "description": "Ооооооо",
        "created_at": "2025-10-15T16:01:07.000000Z",
        "updated_at": "2025-10-15T16:01:07.000000Z",
        "images": [
            {
                "id": 6,
                "product_id": 1,
                "path": "products/01K8175NS6BDF6CS36E05P3Y5N.png",
                "created_at": "2025-10-20T16:27:51.000000Z",
                "updated_at": "2025-10-20T16:27:51.000000Z"
            },
            {
                "id": 7,
                "product_id": 1,
                "path": "products/01K8179BFVT0ZXRVWTRYB505DF.png",
                "created_at": "2025-10-20T16:29:52.000000Z",
                "updated_at": "2025-10-20T16:29:52.000000Z"
            }
        ]
    },
    "message": "",
    "errors": []
    }
```

#### Пример, если товар не найден
```json
{
    "code": "NOT_FOUND",
    "data": null,
    "message": "Товар не найден",
    "errors": []
}
```

## Получение индивидуальной подборки
#### URl: http://localhost:8080/api/v1/personal-compilation
#### METHOD: POST

#### Пример body

```json
{
    "name": "ИВАН ЕБЛАН",
    "birth_date": "30.11.2000"
}
```

#### Пример успешного ответа
```json
{
    "code": "SUCCESS",
    "data": {
        "text": "Привет, TEST! Вот подборка украшений для знака sagittarius.",
        "products": [
            {
                "id": 1,
                "color": "серебряный",
                "composition": "хуевый",
                "price": "11111111.00",
                "inlay": "пидор",
                "lock_type": "английский пидор",
                "length": "110",
                "production": "Уганда",
                "brand": "Азамат",
                "zodiac_sign": "sagittarius",
                "description": "Ооооооо",
                "created_at": "2025-10-15T16:01:07.000000Z",
                "updated_at": "2025-10-15T16:22:40.000000Z",
                "images": [
                    {
                        "id": 6,
                        "product_id": 1,
                        "path": "products/01K8175NS6BDF6CS36E05P3Y5N.png",
                        "created_at": "2025-10-20T16:27:51.000000Z",
                        "updated_at": "2025-10-20T16:27:51.000000Z"
                    },
                    {
                        "id": 7,
                        "product_id": 1,
                        "path": "products/01K8179BFVT0ZXRVWTRYB505DF.png",
                        "created_at": "2025-10-20T16:29:52.000000Z",
                        "updated_at": "2025-10-20T16:29:52.000000Z"
                    }
                ]
            }
        ]
    },
    "message": "",
    "errors": []
}
```
