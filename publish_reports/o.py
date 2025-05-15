import os
import json
import tqdm

wd_tab = {
    "names" : [
        "wd_errors.json",
    ],
    "errs" : {
        'Links to user pages': "wd_user_pages.json",
        'get_csrftoken': "wd_csrftoken.json",
    }
}

main_tab = {
    "names" : [
        "abusefilter-warning.json",
        'errors.json'
    ],
    "errs" : [
        "ratelimited",
        "editconflict",
        "spam filter",
        "captcha",
        "noaccess",
        "abusefilter",
        "mwoauth-invalid-authorization",
    ]
}


reports_dir = os.path.join(os.path.dirname(__file__), 'reports_by_day')

def mv_errors():

    errors_files = {}
    no_names = 0
    for root, dirs, files in os.walk(reports_dir):
        for file in files:
            if file.lower() in main_tab['names']:
                errors_files[os.path.join(root, file)] = file

            elif file.lower() in wd_tab['names']:
                errors_files[os.path.join(root, file)] = file
            else:
                no_names += 1

    print(f"{no_names=}")

    moving_files = {}

    for file_path, old_name in tqdm.tqdm(errors_files.items()):

        with open(file_path, 'r', encoding='utf-8') as f:
            data = json.load(f)

        file_path2 = file_path.replace("/mnt/nfs/labstore-secondary-tools-project/mdwiki/public_html/publish_reports/", "")

        if old_name in main_tab['names']:
            for err in main_tab['errs']:
                if str(data).find(err) != -1:
                    moving_files[file_path] = {"old": old_name, "new": f"{err}.json"}
                    break
        elif old_name in wd_tab['names']:

            for err, new_name in wd_tab['errs'].items():
                if str(data).find(err) != -1:
                    moving_files[file_path] = {"old": old_name, "new": new_name}
                    break
    for n, (file_path, tab) in enumerate(moving_files.items()):
        # ---
        old_name = tab["old"]
        to = tab["new"]
        # ---
        print(f"Moving file: {n} / {len(moving_files)}:")

        file_path2 = file_path.replace("/mnt/nfs/labstore-secondary-tools-project/mdwiki/public_html/publish_reports/", "")

        response = input(f"Rename file ({old_name}) to ({to})? (y/n): ").strip().lower()

        if response in ['y', '']:
            new_path = file_path.replace(old_name, to)
            file_path3 = file_path2.replace(old_name, to)

            os.rename(file_path, new_path)

            print(f"Renamed {file_path2} to {file_path3}")


if __name__ == "__main__":
    mv_errors()
