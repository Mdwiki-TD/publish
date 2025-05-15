import os
import re
import shutil
from datetime import datetime
from pathlib import Path

script_dir = Path(__file__).parent

root_path = script_dir / 'reports_by_day/2025'

# Regex pattern to extract newtimestamp
timestamp_pattern = re.compile(r'"newtimestamp"\s*:\s*"(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z)"')

# Iterate through all paths matching 2025/*/01


def get_day_str(folder_name, day01_path, month_name="", again=True):
    # ---
    day_str = "01"
    # ---
    folder_path = os.path.join(day01_path, folder_name)
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

        if day_str != '01':
            print(f"📅 Found timestamp: {timestamp_str}")
            break
    # ---
    if day_str == '01' and again:
        print(f"⚠️ No timestamp found in folder: {folder_path}")
        # ---
        new_path = script_dir / f"reports/2025/{month_name}"
        # ---
        return get_day_str(folder_name, new_path, month_name=month_name, again=False)
    # ---
    return day_str


for month in os.listdir(root_path):
    month_path = os.path.join(root_path, month)
    day01_path = os.path.join(month_path, '01')

    if not os.path.isdir(day01_path):
        continue

    folder_files = os.listdir(day01_path)

    print(f"\n Folders in: reports_by_day/2025/{month}/01: {len(folder_files)}")

    for folder_name in os.listdir(day01_path):
        folder_path = os.path.join(day01_path, folder_name)

        if not os.path.isdir(folder_path):
            continue

        print(f"_____\n start get_day_str: {folder_name}")
        # ---
        day_str = get_day_str(folder_name, day01_path, month_name=month)

        if day_str == '01':
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
