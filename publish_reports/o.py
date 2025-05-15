import os
import json
import tqdm


def mv_errors():
    reports_dir = os.path.join(os.path.dirname(__file__), 'reports')

    errors_files = []

    for root, dirs, files in os.walk(reports_dir):
        for file in files:
            if file.lower() == 'errors.json':
                errors_files.append(os.path.join(root, file))

    moving_files = {}

    errs = [
        "captcha",
        "noaccess",
        "abusefilter-warning",
    ]
    for file_path in tqdm.tqdm(errors_files):

        with open(file_path, 'r', encoding='utf-8') as f:
            data = json.load(f)

        file_path2 = file_path.replace("/mnt/nfs/labstore-secondary-tools-project/mdwiki/public_html/publish_reports/", "")

        for err in errs:
            if str(data).find(err) != -1:
                moving_files[file_path] = f"{err}.json"
                break

    for n, (file_path, to) in enumerate(moving_files.items()):
        print(f"Moving file: {n} / {len(moving_files)}:")

        file_path2 = file_path.replace("/mnt/nfs/labstore-secondary-tools-project/mdwiki/public_html/publish_reports/", "")

        response = input(f"Rename this file to {to}? (y/n): ").strip().lower()

        if response in ['y', '']:
            new_path = file_path.replace("errors.json", to)
            file_path3 = file_path2.replace("errors.json", to)

            os.rename(file_path, new_path)

            print(f"Renamed {file_path2} to {file_path3}")


def mv_wd_errors():
    reports_dir = os.path.join(os.path.dirname(__file__), 'reports')

    errors_files = []

    for root, dirs, files in os.walk(reports_dir):
        for file in files:
            if file.lower() == 'wd_errors.json':
                errors_files.append(os.path.join(root, file))

    moving_files = []

    for file_path in tqdm.tqdm(errors_files):

        with open(file_path, 'r', encoding='utf-8') as f:
            data = json.load(f)

        file_path2 = file_path.replace("/mnt/nfs/labstore-secondary-tools-project/mdwiki/public_html/publish_reports/", "")

        if str(data).find('Links to user pages') != -1:
            moving_files.append(file_path)

    for n, file_path in enumerate(moving_files):
        print(f"Moving file: {n} / {len(moving_files)}:")

        file_path2 = file_path.replace("/mnt/nfs/labstore-secondary-tools-project/mdwiki/public_html/publish_reports/", "")

        response = input("Rename this file to wd_user_pages.json? (y/n): ").strip().lower()

        if response in ['y', '']:
            new_path = file_path.replace("wd_errors.json", "wd_user_pages.json")
            file_path3 = file_path2.replace("wd_errors.json", "wd_user_pages.json")

            os.rename(file_path, new_path)

            print(f"Renamed {file_path2} to {file_path3}")


if __name__ == "__main__":
    mv_errors()
    mv_wd_errors()
