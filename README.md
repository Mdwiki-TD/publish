
# Overview
This script is used to process the publishing of Wikipedia articles via [ContentTranslation tool](https://github.com/mdwikicx/cx-1).

# How it's working
Before publishing to Wikipedia, this process uses the [fix_refs](https://github.com/Mdwiki-TD/fix_refs) repository to make several changes to the wikitext. These changes include:

* **Fixing References:** Correcting and standardizing reference formatting.
* **Expanding Infoboxes:** Enhancing infoboxes with more relevant information.
* **Adding Categories:** Ensuring appropriate categories are assigned to the articles.
* **Other changes** adding and correcting other minor issues in wikitext.

These pre-processing steps help ensure the quality and consistency of articles published to Wikipedia.
