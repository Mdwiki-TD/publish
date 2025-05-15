import os
import sys
import re
import shutil
from datetime import datetime, timedelta
from pathlib import Path

script_dir = Path(__file__).parent

root_path = script_dir / 'reports_by_day/2025'

# Regex pattern to extract newtimestamp
timestamp_pattern = re.compile(r'"newtimestamp"\s*:\s*"(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z)"')

# Iterate through all paths matching 2025/*/01


def get_time_of_changes(new_path, day_orginal):
    # ---
    day_str = day_orginal
    # ---
    if not os.path.isdir(new_path):
        return day_str
    # ---
    # print(f"{new_path} is a directory")
    # ---
    for file_name in os.listdir(new_path):
        if not file_name.endswith('.json'):
            continue

        file_path = os.path.join(new_path, file_name)

        file_date = os.path.getmtime(file_path)
        file_date = datetime.fromtimestamp(file_date)
        # ---
        # if file_date date == today then continue
        # ---
        if file_date.strftime('%Y-%m-%d') == datetime.now().strftime('%Y-%m-%d'):
            continue
        # ---
        # if file_date date == yesterday then continue
        # ---
        if file_date.strftime('%Y-%m-%d') == (datetime.now() - timedelta(days=1)).strftime('%Y-%m-%d'):
            continue
        # ---
        day_str = file_date.strftime('%d')

        if day_str != day_orginal:
            print(f"📅 def get_time_of_changes(): Found timestamp: {file_date}")
            break
    # ---
    return day_str


def get_day_str(folder_path, day_orginal):
    # ---
    day_str = day_orginal
    # ---
    if not os.path.isdir(folder_path):
        return day_str
    # ---
    for file_name in os.listdir(folder_path):
        if not file_name.endswith('.json'):
            continue

        file_path = os.path.join(folder_path, file_name)

        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()

        match = timestamp_pattern.search(content)
        if not match:
            continue

        timestamp_str = match.group(1)
        timestamp = datetime.fromisoformat(timestamp_str.replace("Z", "+00:00"))
        file_month = timestamp.strftime('%Y-%m')
        folder_month = f'2025-{month}'

        # Check if the month matches
        if file_month != folder_month:
            continue

        day_str = timestamp.strftime('%d')

        if day_str != day_orginal:
            print(f"📅 def get_day_str(): Found timestamp: {timestamp_str}")
            break
    # ---
    return day_str


get_time_of_files = "notime" not in sys.argv
only_month = ""
day_orginal = "01"
# ---
for arg in sys.argv:
    arg, _, value = arg.partition(':')
    # ---
    if arg == '-m':
        only_month = value
    # ---
    elif arg == '-d':
        day_orginal = value

# ---
for month in os.listdir(root_path):
    # ---
    if only_month and month != only_month:
        continue
    # ---
    month_path = os.path.join(root_path, month)
    # ---
    print("month_path:", os.listdir(month_path))
    # ---
    for day in os.listdir(month_path):
        # ---
        if day_orginal != "all":
            if day != day_orginal:
                continue
        # ---
        day01_path = os.path.join(month_path, day)

        if not os.path.isdir(day01_path):
            continue

        folder_files = os.listdir(day01_path)

        print(f"\n Folders in: reports_by_day/2025/{month}/{day}: {len(folder_files)}")

        folder_filesx = os.listdir(script_dir / f'reports/2025/{month}')
        print(f"\n Folders in: reports/2025/{month}: {len(folder_filesx)}")

        for folder_name in folder_files:
            folder_path = os.path.join(day01_path, folder_name)

            if not os.path.isdir(folder_path):
                continue

            print(f"_____\n start get_day_str: {folder_name}")
            # ---
            day_str = get_day_str(folder_path, day)

            if day_str == day and get_time_of_files:
                # ---
                new_path = script_dir / f"reports/2025/{month}/{folder_name}"
                # ---
                day_str = get_time_of_changes(new_path, day)
            # ---
            if day_str == day:
                continue

            # Target path for the new day
            new_day_path = os.path.join(month_path, day_str)
            new_folder_path = os.path.join(new_day_path, folder_name)

            print(f"\n📁 Folder: {folder_path}")
            print(f"➡️ Suggested move to: {new_folder_path}")
            response = input("Do you want to move this folder? (y/n): ").strip().lower()

            if response in ['y', '']:
                if not os.path.exists(new_day_path):
                    os.makedirs(new_day_path)
                shutil.move(folder_path, new_folder_path)
                print(f"✅ Moved to {new_folder_path}")
            else:
                print("⏩ Skipped based on user input.")
