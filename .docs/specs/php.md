# PHP Code Style Spec

## Purpose
This file defines the PHP rules for the project.
Goal: keep PHP code simple, readable, predictable, maintainable, and easy to control in AI-assisted development.

## PHP Stack
- PHP 8+
- SQLite / MySQL - by user choice
- PDO
- Router
- MVC
- Templates in `templates/` with `.tpl` extension

## Main Approach
- Prefer simple procedural and function-based PHP
- Do not build the project around heavy OOP
- OOP is allowed only where technically justified:
  - PDO
  - external libraries
  - small infrastructure classes if really needed
- Business logic should stay simple and explicit
- Code must be easy to read and fix manually

## What We Use
- functions
- clear file structure
- PDO prepared statements
- simple MVC separation
- explicit validation
- explicit routing
- template rendering through `.tpl` files

## What We Do Not Use
- no unnecessary OOP layers
- no ORM
- no framework-specific patterns
- no magic methods as core architecture
- no overengineering
- no abstract classes/interfaces without real need
- no hidden side effects
- no logic inside templates
- no SQL inside templates

## Project Structure
Main PHP-related structure:

- `app/controllers/` — request handling
- `app/models/` — database queries and data access
- `app/services/` — isolated application logic (uploads, editor processing, image handling)
- `app/validators/` — input validation
- `app/helpers/` — helper functions
- `app/core/` — router, db connection, rendering, app bootstrap
- `templates/` — `.tpl` templates
- `config/` — app config, db config, routes config
- `index.php` — front controller entry point

## Responsibility Rules

### Controllers
Controllers:
- receive request
- read input data
- call validator if needed
- call model/service
- prepare data for template
- return/render response

Controllers must not:
- contain SQL
- contain large business logic
- generate HTML directly
- process uploaded files inline if that logic can be isolated

### Models
Models:
- work with database
- contain SQL queries
- return structured data

Models must not:
- render templates
- contain presentation logic
- contain unrelated side logic

### Services
Services:
- contain isolated application logic
- process files
- process images
- process Editor.js data
- contain reusable workflow logic

Services must not:
- render templates
- replace controllers or models

### Validators
Validators:
- validate input
- sanitize when needed
- return clear errors

Validation must be explicit and readable.

### Templates
Templates:
- display data only
- use `.tpl` extension
- stay simple

Templates must not:
- contain SQL
- contain business logic
- contain heavy condition chains
- mutate application state

## Coding Style
- write simple and direct code
- prefer readability over cleverness
- keep functions short
- keep nesting low
- use early returns where appropriate
- avoid hidden logic
- avoid large universal helpers without real need
- one file = one main responsibility

## Functions
Prefer small named functions.

Rules:
- function name must clearly describe action
- function should do one thing
- avoid giant multifunction handlers
- pass only required arguments
- return predictable result

Good style:
- `getPostBySlug()`
- `createPost()`
- `updatePost()`
- `validatePostData()`
- `saveUploadedImage()`

Bad style:
- `handleEverything()`
- `processData()`
- `run()`
- `doTask()`

## Naming
- use clear English names in code
- avoid random abbreviations
- variables, functions, and files must be understandable
- use one naming style across the project

Recommended:
- `postTitle`
- `postSlug`
- `uploadedFile`
- `validationErrors`

Avoid:
- `data1`
- `tmpX`
- `resFinal`
- `a`, `b`, `c` except tiny local loop variables

## File Rules
- one file = one responsibility
- related logic should stay together
- do not mix controller, model, and helper logic in one file
- do not create deeply fragmented micro-files without need

## Routing
- routes must be defined centrally
- router must be simple and explicit
- routing must not contain business logic
- request -> controller -> model/service -> template

## Database Rules
- use SQLite for current project scope
- access DB through PDO only
- use prepared statements only
- keep SQL explicit and readable
- schema must match `database-schema.md`

Do not:
- concatenate unsafe user input into SQL
- hide queries behind unnecessary abstractions
- spread SQL randomly across the project

## Error Handling
- errors must be handled predictably
- internal errors must not be shown directly to users
- validation errors should be clear
- technical errors should go to logs where needed

## Security Rules
- validate all external input
- sanitize where appropriate
- use prepared statements
- escape output in templates
- validate uploaded files by size, type, and extension
- never trust raw user input

## Templates and Rendering
- templates live in `templates/`
- extension: `.tpl`
- rendering should be centralized
- controller passes prepared data into template
- template displays only what it receives

## Editor / Content Handling
- Editor.js is used as WYSIWYG editor
- editor content should be processed in isolated logic
- storage format should be explicit and predictable
- do not mix raw editor processing into unrelated files

## Uploads and Images
- image processing should be isolated
- use GD for raster image handling
- raster formats: jpg, png
- SVG for icons and vector graphics
- prepare 2x image versions where required for retina support
- file handling logic should live in services/helpers, not templates

## AI Rules for PHP
AI must:
- follow this PHP structure
- keep MVC responsibilities clean
- prefer functions and explicit logic
- keep code simple and readable
- use PDO prepared statements
- place code in correct directories
- avoid unrelated refactoring
- preserve current architecture

AI must not:
- introduce framework-like abstractions
- rewrite the whole project for small changes
- add unnecessary OOP
- mix responsibilities between layers
- place SQL in templates/controllers
- invent new stack decisions without request

## Definition of Done
PHP implementation is complete when:
- code follows the defined project structure
- responsibilities are separated correctly
- controllers stay thin
- models contain DB logic
- templates stay clean
- validation is explicit
- SQL uses prepared statements
- code is readable and maintainable manually
- implementation matches project specs and current phase