#!/usr/bin/env python3
"""

python3 core8/pwb.py I:/mdwiki/publish-repo/reports/db localhost
python3 core8/pwb.py public_html/publish_reports/db.py


"""
import sys
import os
import json
import tqdm
from datetime import datetime

from mdapi_sql import sql_for_mdwiki_new
# sql_for_mdwiki_new.mdwiki_sql(query, values=None)
from pathlib import Path



# print(os.getenv("HOME"))

done = []
Duplicate = 0
added = 0

in_sql = sql_for_mdwiki_new.mdwiki_sql("select * from publish_reports", return_dict=True)

for x in in_sql:
    # print(x)
    # (`date`, `title`, `user`, `lang`, `sourcetitle`, `result`)
    date = x['date'].strftime("%Y-%m-%d")
    result = x['result'].replace(".json", "")
    done_key = tuple([x['title'], x['user'], x['lang'], x['sourcetitle'], result, date])
    if done_key in done:
        Duplicate += 1
    else:
        done.append(done_key)


def add_report(query, params):
    """Placeholder for the add_report function."""
    sql_for_mdwiki_new.mdwiki_sql(query, values=params)

reports_dir = Path(__file__).parent / "reports_by_day"

json_files = []

for root, _, files in os.walk(reports_dir):
    for file in files:
        if file.endswith(".json"):
            file_path = os.path.join(root, file)
            json_files.append(file_path)


for file_path in tqdm.tqdm(json_files, desc="Processing reports"):
    try:
        with open(file_path, "r", encoding="utf-8") as f:
            data = json.load(f)
    except Exception as e:
        print(f"Error reading file {file_path}: {e}")
        continue

    # Extract data from file
    title = data.get("title", "")
    user = data.get("user", "") or data.get("username", "")
    lang = data.get("lang", "")
    sourcetitle = data.get("sourcetitle", "")
    report_data = json.dumps(data)  # Serialize the entire data dictionary to JSON

    if not title and not user and not lang:
        continue

    # Extract date from file or path
    time_date = data.get("time_date")

    if time_date:
        date_obj = datetime.strptime(time_date, "%Y-%m-%d %H:%M:%S")  # Adjust format as needed
    else:
        # Extract date from file path
        day = Path(file_path).parent.parent.name
        month = Path(file_path).parent.parent.parent.name
        year = Path(file_path).parent.parent.parent.parent.name
        # print(day, month)

        if str(year) == '2025' and month.isnumeric() and day.isnumeric():
            year = 2025
            date_obj = datetime(2025, int(month), int(day))

    result = Path(file_path).name

    # Convert datetime object to string
    date_str = date_obj.strftime("%Y-%m-%d")

    result2 = result.replace(".json", "")

    done_key = tuple([title, user, lang, sourcetitle, result2, date_str])

    if done_key in done:
        Duplicate += 1
        continue

    done.append(done_key)

    # Construct the SQL query
    query = "INSERT INTO publish_reports (`date`, `title`, `user`, `lang`, `sourcetitle`, `result`, `data`) VALUES (%s, %s, %s, %s, %s, %s, %s)"
    params = (date_str, title, user, lang, sourcetitle, result2, report_data)

    # Pass the query to add_report function
    add_report(query, params)
    added += 1

print(f"Duplicate: {Duplicate}")

print(f"added: {added}")
