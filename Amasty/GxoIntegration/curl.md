TOKEN="blpusghg0zegtvvafq03w75uxqy71475"
BASE_URL="https://minisoshop--custdev.ap83.amasty.net/"

curl -X POST "$BASE_URL/rest/V1/amasty/gxo/shipment" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "ORDER_ID": "000068543",
    "CUSTOMER_ID": "GUEST",
    "SHIPPING_STATUS": "SHIPPED",
    "LINES": [
      { "LINE_ID": "1", "SKU_ID": "D365-ITEM-001", "QTY": 1, "EXPECTED_QTY": 1 },
      { "LINE_ID": "2", "SKU_ID": "D365-ITEM-002", "QTY": 1, "EXPECTED_QTY": 1 }
    ]
  }'


TOKEN="blpusghg0zegtvvafq03w75uxqy71475"
BASE_URL="https://minisoshop--custdev.ap83.amasty.net/"

curl -X POST "$BASE_URL/rest/V1/amasty/gxo/shipment" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "payload": [{
      "shipConf": {
        "ORDER_ID": "000053098",
        "CUSTOMER_ID": "GUEST",
        "orderLines": {
          "orderLine": [
            { "LINE_ID": "1", "SKU_ID": "2012455210109", "QTY": "2", "EXPECTED_QTY": "3" },
            { "LINE_ID": "2", "SKU_ID": "2000003312108", "QTY": "1", "EXPECTED_QTY": "2" }
          ]
        },
        "parcels": [
          {
            "carrierContainerId": "TT021109330GB",
            "containerId": "9000954447",
            "SHIPPING_STATUS": "https://www.royalmail.com/track-your-item",
            "lines": [
              { "LINE_ID": "1", "SKU_ID": "2012455210109", "QTY": "2" }
            ]
          },
          {
            "carrierContainerId": "TT021109343GB",
            "containerId": "9000954448",
            "SHIPPING_STATUS": "https://www.royalmail.com/track-your-item",
            "lines": [
              { "LINE_ID": "2", "SKU_ID": "2000003312108", "QTY": "1" }
            ]
          }
        ]
      }
    }]
  }'

TOKEN=""
BASE_URL=""

curl -X POST "$BASE_URL/rest/V1/amasty/gxo/inventory/snapshot" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "COMPANY_CODE": "gb01",
    "ROWS": [
      { "SKU_ID": "D365-ITEM-001", "CONDITION_ID": "AVAILABLE", "QTY_ON_HAND": 6, "TRACKING_LEVEL": "EACH", "DSTAMP": "10/14/2025 13:42", "ORIGIN_ID": "ECOM" },
      { "SKU_ID": "D365-ITEM-002", "CONDITION_ID": "AVAILABLE", "QTY_ON_HAND": 5, "TRACKING_LEVEL": "EACH", "DSTAMP": "10/14/2025 13:42", "ORIGIN_ID": "ECOM" }
    ]
  }'