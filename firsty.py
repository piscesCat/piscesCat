# Script by Khải Phan
# Shared on discord @kenhtaymay
# Dont remove this line. Không xoá dòng này để tôn trọng tác giả.

import requests

url = "https://backoffice.firsty.app/api/mobile/subscriptions/v2/i7Jzwq9Gx7PPaDEYmdFHxYMLocJ3/iccid/893107062434917784/free"

headers = {
    "user-agent": "Dart/3.4 (dart:io)",
    "accept-encoding": "gzip",
    "authorization": "Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6ImNlMzcxNzMwZWY4NmViYTI5YTUyMTJkOWI5NmYzNjc1NTA0ZjYyYmMiLCJ0eXAiOiJKV1QifQ.eyJuYW1lIjoiS2jhuqNpIiwicGljdHVyZSI6Imh0dHBzOi8vbGgzLmdvb2dsZXVzZXJjb250ZW50LmNvbS9hL0FDZzhvY0pJUExkRE9BWUI2SV9vWlVGZTA4cV96ZTVPS2ZnREZhbURKakswWjN2VzJLQUZhdz1zOTYtYyIsImlzcyI6Imh0dHBzOi8vc2VjdXJldG9rZW4uZ29vZ2xlLmNvbS9maXJzdHktcHJvZCIsImF1ZCI6ImZpcnN0eS1wcm9kIiwiYXV0aF90aW1lIjoxNzIzMjI1MTE2LCJ1c2VyX2lkIjoiaTdKendxOUd4N1BQYURFWW1kRkh4WU1Mb2NKMyIsInN1YiI6Imk3Snp3cTlHeDdQUGFERVltZEZIeFlNTG9jSjMiLCJpYXQiOjE3MjMyMjg2NTQsImV4cCI6MTcyMzIzMjI1NCwiZW1haWwiOiJrNDg3NDYxM0BnbWFpbC5jb20iLCJlbWFpbF92ZXJpZmllZCI6dHJ1ZSwiZmlyZWJhc2UiOnsiaWRlbnRpdGllcyI6eyJnb29nbGUuY29tIjpbIjEwNTM0MTg3NDU1OTMxNjA2MTY5NSJdLCJlbWFpbCI6WyJrNDg3NDYxM0BnbWFpbC5jb20iXX0sInNpZ25faW5fcHJvdmlkZXIiOiJnb29nbGUuY29tIn19.EZkELYRe8G-oCe_Qvna40lRRcpYed9mCtULTMrkXqdAn4DxwP8e3DvzoufjaM_5-hmyhsMurrz2Zu7w7p_LU122dMe9j18r6A5FQGCeqKAodVpEpZlDNTZ61h-AXPkpQBPcVuj92bzC28yQPS8Yu1iFGWj31uZbiHkofy5VuLbw_zx5UDC0x_MwWJq1iTAtVpwzUDEJFppZvCsqNPURU365AevyPnjOWe9J9I_wrTjhMqKSoHxjMAUusZ1YuwoDSddfcneUbQqDJdtW9nTgSOAEoNnSOQpA7peGmJEvpAt8kosP1q30VDndXMscrbW3Nv0tN6mcWgT_rcgJmXndJ-A",
    "app-build": "3938",
    "content-type": "application/json",
    "app-version": "1.0.352"
}

data = {
    "planType": "ADVERTISEMENT",
    "deviceId": "5993C521-924B-4808-9D36-24AFAB569C3F",
    "country": "US"
}

response = requests.post(url, headers=headers, json=data)

if response.status_code == 200:
    print(response.json())
else:
    print(f"Request failed with status code: {response.status_code}")
