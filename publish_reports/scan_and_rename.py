import json
import re
import os
from pathlib import Path
from datetime import datetime
import shutil

def process_year():
    script_dir = Path(__file__).parent
    year_path = script_dir / 'reports_by_day' / '2025'
    timestamp_pattern = re.compile(r'"newtimestamp"\s*:\s*"(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z)"')

    # Process each month folder
    for month_folder in os.listdir(year_path):
        month_path = year_path / month_folder
        if not month_path.is_dir() or not month_folder.isdigit():
            continue

        month = int(month_folder)
        day01_path = month_path / '01'
        if not day01_path.exists():
            continue

        print(f"\nProcessing month: {month:02d}")
        folder_to_move_done = []

        # Process each subfolder in day01 folder
        for folder_name in os.listdir(day01_path):
            folder_path = day01_path / folder_name
            if not folder_path.is_dir():
                continue

            # Find first JSON file in folder
            json_files = list(folder_path.glob('*.json'))
            if not json_files:
                continue

            json_file = json_files[0]
            try:
                with open(json_file, 'r', encoding='utf-8') as f:
                    content = f.read()
                match = timestamp_pattern.search(content)
                if not match:
                    continue

                timestamp = match.group(1)
                dt = datetime.strptime(timestamp, "%Y-%m-%dT%H:%M:%SZ")

                # Skip if month doesn't match folder
                if dt.month != month:
                    continue

                # Skip if day is 01
                if dt.day == 1:
                    continue

                # Prepare move operation
                target_day = str(dt.day).zfill(2)
                target_folder = month_path / target_day
                dest_path = target_folder / folder_path.name

                # Ask for user confirmation
                print(f"\nFound folder to move: {folder_path}")
                print(f"Timestamp: {timestamp}")
                print(f"Target location: {dest_path}")
                response = input("Move this folder? (y/n): ").strip().lower()

                if response in ["y", ""]:
                    target_folder.mkdir(exist_ok=True)
                    if not dest_path.exists():
                        shutil.move(str(folder_path), str(dest_path))
                        print(f"Moved {folder_path} to {dest_path}")
                        folder_to_move_done.append(folder_path)
                    else:
                        print(f"Target already exists: {dest_path}")
                else:
                    print("Skipping this folder")

            except (json.JSONDecodeError, ValueError) as e:
                print(f"Error processing {json_file}: {str(e)}")

if __name__ == "__main__":
    process_year()
