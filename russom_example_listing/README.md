# WG Directory Listing

Vue application (v 2.5.5) for filtering and sorting published companies. 

Drupal 8 outputs JSON for live filtering and sorting. 

- JSON output url: `/api/company/russom_example_listing_endpoint.json?_format=json`
- Drupal 8.5 and higher require `?_format=json` appended to the URL. 

Vue application filters on JSON output, located in `src` directory. 
 
## Vue application
- Vue application is located in `js` directory. 

### Methods, computed

- Methods: 
    - `orderByInteger`, function to order integer values. 
    - `orderByString`, function to order string values.
    - `taxonomyOptions`, provides array for taxonomy select options.
    - `multipleValues`, filters on multiple values.
    - `yearFounded`, calculates years in business from year founded value. 
    - `formSubmit`, resets form options.
- Computed:
    - `filter`, handles filtering of input and select fields. 
    - `locationOptions`, provides custom array of company locations  - grouped by state. 
    - `teaserImage`, handles image teaser as background image style. Falls back to no image css treament. 

### Component

- `select-chosen` - Handles chosen.js implementation of select field.
- `star-rating` - Outputs rating average of company.

## Libraries
This module loads the following libraries via composer: 

- Chosen.js
- Vue.js

PHP library from the `votingapi_widgets` module is used to get voting averages of company nodes.

## Developer notes
To enable developer mode: 

1) Add the following to `russom-example-template.html.twig` file:
    ```
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    ```
2) Comment out the following in `russom_example_listing.libraries.yml` lines: 
    ```
    vue:
      version: VERSION
      js:
        /libraries/vue/dist/vue.min.js: { minified: true }
    ```
    
This will output a console log message in your browser to indicate developer mode as been activated. 
