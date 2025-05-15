import os
import json
import tqdm

reports_dir = os.path.join(os.path.dirname(__file__), 'reports')

print(f"Removing files from {reports_dir}")

user_to_del = "ابراهيم أحمد2025"

all_files = []

for root, dirs, files in os.walk(reports_dir):
    for file in files:
        if str(file).endswith('.json'):
            all_files.append(os.path.join(root, file))

data_rm = "{'title': '', 'summary': 'Created by translating the page [[:mdwiki:Special:Redirect/revision/|]] to: #mdwikicx', 'lang': '', 'user': '', 'campaign': '', 'result': '', 'edit': {'error': {'code': 'noaccess', 'info': 'noaccess'}, 'edit': {'error': {'code': 'noaccess', 'info': 'noaccess'}, 'username': ''}, 'username': ''}, 'sourcetitle': ''}"

removed_empty = 0
removed = 0

for file_path in tqdm.tqdm(all_files):
    # print(file_path)
    with open(file_path, 'r', encoding='utf-8') as f:
        data = json.load(f)

    user_fin = data.get("user") or data.get("username", "")

    if str(data).find(user_to_del) != -1 or user_fin == user_to_del:
        os.remove(file_path)
        print(f"Removed file: {file_path}")
        removed += 1

    elif str(data) == data_rm:
        os.remove(file_path)
        print(f"Removed empty file: {file_path}")
        removed_empty += 1


print(f"Removed files: {removed}")

print(f"removed empty files: {removed_empty}")
