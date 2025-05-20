# Wikipedia Article Publishing Workflow

## Overview
This project is a PHP-based workflow designed to manage the final steps for publishing Wikipedia articles translated via the ContentTranslation tool. The system pre-processes wikitext, prepares API requests, and publishes articles while also linking them to Wikidata after successful edits.

## Features
- **Wikitext Pre-Processing**: Cleans and standardizes Wikipedia articles using `fix_refs` and `textfixes` components.
- **Automated Publishing**: Constructs API requests and submits edits to Wikipedia.
- **Wikidata Integration**: Links newly published articles to corresponding Wikidata items.
- **Logging and Reporting**: Maintains logs of published edits and errors.
- **Automation Support**: Includes bot components for additional auxiliary tasks.
- **Configuration Management**: Uses centralized config files for easy management.
- **Testing Suite**: Ensures reliability with test cases.

## System Architecture
### Core Components
1. **Entry Point / Controller**
   - `index.php`: Orchestrates the workflow, initiating the publishing process.

2. **Pre-Processing Module** (Wikitext Processing)
   - `fix_refs` directory
   - `textfixes` directory (including `textfixes/text_fix.php`, `textfixes/text_fix_refs.php`)

3. **API Preparation & Processing Module** (API interaction with Wikipedia)
   - `do_edit.php`: Handles article editing requests.
   - `get_token.php`: Manages authentication and token retrieval.
   - `token.php`: Assists with authentication handling.

4. **Bot/Automation Components**
   - `bots/` directory:
     - `bots/index.php`
     - `bots/mdwiki_sql.php`
     - `bots/wd.php`

5. **Reporting & Logging Module**
   - `publish_reports/` directory:
     - `publish_reports/index.php`
   - `text_changes.php`: Tracks and logs text modifications.

6. **Configuration and Dependency Management**
   - `config.php`: Stores global settings and configurations.
   - `include.php`: Centralized helper functions for shared use.

7. **Testing Suite**
   - `tests/` directory:
     - `tests/test.php`

## Workflow
1. `index.php` starts execution (via web request or scheduled task).
2. Reads external JSON file (`all_pages_revids.json`) to retrieve revision IDs.
3. Generates an edit summary using `make_summary`.
4. Sends the wikitext through `fix_refs` and `textfixes` for cleanup and standardization.
5. API request parameters are prepared (`prepareApiParams`).
6. The system processes edits (`processEdit`) and submits them to Wikipedia.
7. On successful edit, `handleSuccessfulEdit` links the article to Wikidata.
8. Additional bot scripts (`bots/`) and reporting modules (`publish_reports/`) run as needed.
9. Error handling and logging mechanisms track and resolve issues (`handleNoAccess`).

## Design Principles
- **Layered Architecture**: Separation of concerns between controllers, business logic, external integrations, and auxiliary modules.
- **Modular Design**: Reusable helper functions and configuration management.
- **Automation-Friendly**: Bots and scripts streamline workflows.
- **API-Driven**: Integrates with MediaWiki API and Wikidata for seamless publishing.

## Technologies Used
- **PHP** (Primary language)
- **MediaWiki API** (For Wikipedia publishing)
- **Wikidata API** (For article linking)
- **GitHub Actions** (For automation and workflow management)


## Diagram
```mermaid
flowchart TD
    %% Input Sources
    A["Translated Wikitext Input"]:::input
    B["all_pages_revids.json"]:::input

    %% Front-End / Entry Points
    subgraph "Front-End / Entry Points"
        MP["index.php"]:::entrypoint
        IP["index.php"]:::entrypoint
        DEP["do_edit.php"]:::entrypoint
    end

    %% Configuration & Helpers
    subgraph "Configuration & Helpers"
        CP["config.php"]:::config
        INC["include.php"]:::config
        TP["token.php"]:::config
        GT["get_token.php"]:::config
        HP["helps.php"]:::config
        TFINC["textfixes/include.php"]:::config
    end

    %% Text Pre-processing Modules
    subgraph "Text Pre-processing Modules"
        FR["fix_refs (Integration)"]:::processing
        TF["Textfixes Modules (Citation, md_cat, text_fix, text_fix_refs, index.php)"]:::processing
    end

    %% API Preparation and External API
    AP["API Preparation"]:::processing
    subgraph "External API"
        API["Wikipedia API"]:::external
        WD["Wikidata Linking"]:::external
    end

    %% Auxiliary/Bot Services
    subgraph "Auxiliary/Bot Services"
        BOT["bots (bots/index.php, mdwiki_sql.php, wd.php)"]:::bot
    end

    %% Testing & Quality Assurance
    subgraph "Testing & Quality Assurance"
        TEST["tests (test.php)"]:::test
    end

    %% Data Flow Connections
    A --> MP
    B --> MP

    CP --> MP
    INC --> MP
    TP --> MP
    GT --> MP
    HP --> MP
    TFINC --> MP

    MP -->|"DataTransformation"| FR
    MP -->|"DataTransformation"| TF

    FR -->|"ProcessedText"| AP
    TF -->|"ProcessedText"| AP

    AP -->|"APIRequest"| API
    API -->|"SuccessfulEdit"| WD

    MP -.->|"ErrorHandling"| BOT
    MP -.->|"Validation"| TEST

    %% Styles
    classDef entrypoint fill:#f9f,stroke:#333,stroke-width:2px;
    classDef config fill:#bbf,stroke:#333,stroke-width:2px;
    classDef processing fill:#bfb,stroke:#333,stroke-width:2px;
    classDef external fill:#ffe,stroke:#333,stroke-width:2px;
    classDef bot fill:#fbb,stroke:#333,stroke-width:2px;
    classDef test fill:#efc,stroke:#333,stroke-width:2px;
    classDef input fill:#fcc,stroke:#333,stroke-width:2px;

    %% Click Events for Component Mapping
    click MP "https://github.com/mdwiki-td/publish/blob/main/index.php"
    click IP "https://github.com/mdwiki-td/publish/blob/main/index.php"
    click DEP "https://github.com/mdwiki-td/publish/blob/main/do_edit.php"
    click FR "https://github.com/mdwiki-td/publish/tree/main/fix_refs"
    click TF "https://github.com/mdwiki-td/publish/tree/main/textfixes"
    click CP "https://github.com/mdwiki-td/publish/blob/main/config.php"
    click INC "https://github.com/mdwiki-td/publish/blob/main/include.php"
    click TP "https://github.com/mdwiki-td/publish/blob/main/token.php"
    click GT "https://github.com/mdwiki-td/publish/blob/main/get_token.php"
    click HP "https://github.com/mdwiki-td/publish/blob/main/helps.php"
    click TFINC "https://github.com/mdwiki-td/publish/blob/main/textfixes/include.php"
    click BOT "https://github.com/mdwiki-td/publish/tree/main/bots"
    click TEST "https://github.com/mdwiki-td/publish/blob/main/tests/test.php"
```
