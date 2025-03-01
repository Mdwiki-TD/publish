# Overview
This repository manages the final steps in the process of publishing Wikipedia articles that have been translated using the [ContentTranslation tool](https://github.com/mdwikicx/cx-1) in [medwiki.toolforge.org](http://medwiki.toolforge.org/). It takes the translated text in wikitext format, refines it further, and then publishes it to Wikipedia.

# How it's working
Before publishing to Wikipedia, this process uses the [fix_refs](https://github.com/Mdwiki-TD/fix_refs) repository to make several changes to the wikitext. These changes include:

* **Fixing References:** Correcting and standardizing reference formatting.
* **Expanding Infoboxes:** Enhancing infoboxes with more relevant information.
* **Adding Categories:** Ensuring appropriate categories are assigned to the articles.
* **Other changes:** Adding and correcting other minor issues in wikitext.

These pre-processing steps help ensure the quality and consistency of articles published to Wikipedia.

# Usage
The main script to run is `main.php`. It processes the publishing of Wikipedia articles by performing the following steps:

1. **Read Revision ID:** The script reads the `all_pages_revids.json` file to get the revision ID of the source title.
2. **Make Summary:** Creates a summary for the edit based on the revision ID, source title, target language, and hashtag.
3. **Pre-process Text:** Uses the `fix_refs` repository to make necessary changes to the wikitext.
4. **Prepare API Parameters:** Prepares the parameters needed for the Wikipedia API request.
5. **Handle No Access:** If the user does not have access, it logs the error.
6. **Process Edit:** If the user has access, it processes the edit by making the API request to Wikipedia.
7. **Handle Successful Edit:** If the edit is successful, it links the article to Wikidata and logs the result.

# Functions
The main functions in `main.php` include:

- `get_revid($sourcetitle)`: Reads the revision ID from `all_pages_revids.json`.
- `make_summary($revid, $sourcetitle, $to, $hashtag)`: Creates the edit summary.
- `to_do($tab, $dir)`: Logs the tasks to a file.
- `formatTitle($title)`: Formats the title.
- `formatUser($user)`: Formats the user name.
- `determineHashtag($title, $user)`: Determines the hashtag to be used.
- `prepareApiParams($title, $summary, $text, $request)`: Prepares the API parameters for the edit request.
- `handleNoAccess($user, $tab)`: Handles cases where the user does not have access.
- `processEdit($access, $sourcetitle, $text, $lang, $revid, $campaign, $user, $title, $summary, $request, $tab)`: Processes the edit request.
- `handleSuccessfulEdit($sourcetitle, $campaign, $lang, $user, $title, $editit, $access_key, $access_secret)`: Handles successful edits and links the article to Wikidata.
- `start($request)`: The main function that starts the process based on the request parameters.

# Notes
- Ensure that the necessary dependencies and files, such as `all_pages_revids.json`, are available in the directory.
- The script uses several helper functions from other included files, such as `fix_refs`, `DoChangesToText`, and `LinkToWikidata`.

# License
This project does not currently specify a license.
