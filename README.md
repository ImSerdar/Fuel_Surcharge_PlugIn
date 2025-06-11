# Weekly Diesel Fuel Sucharge Updater For Vancouver, BC region for WordPress

The Weekly Diesel Fuel Sucharge Updater For Vancouver, BC region is a WordPress plugin designed to automate the process of fetching, processing, and displaying weekly data, specifically focusing on fuel surcharge percentages. This plugin simplifies the management of dynamic data within WordPress, ensuring your site always displays the most current information.

## Features

- **Automated Data Fetching**: Automatically downloads Excel files containing the latest data on a weekly schedule.
- **Data Processing**: Parses Excel files and updates WordPress database with new data.
- **Shortcode Integration**: Provides a shortcode `[my_table]` for easy insertion of the data table into posts and pages.
- **Highlight Current Week**: Automatically highlights the row corresponding to the current week, enhancing user comprehension.

## Installation

To install the Weekly Data Updater plugin:

1. Clone this repository or download the ZIP file.  
2. Upload the plugin files to the `/wp-content/plugins/weekly-data-updater` directory on your WordPress site.  
   - **Install Composer dependencies**  
     ```bash
     cd wp-content/plugins/weekly-data-updater
     composer require phpoffice/phpspreadsheet
     ```  
   - **Be sure** the resulting `vendor/` folder is deployed alongside the plugin files so that `vendor/autoload.php` can be required.  
3. Activate the plugin through the 'Plugins' menu in WordPress.
## Usage

Insert the shortcode `[my_table]` in any post or page where you want the weekly data table to appear. The plugin takes care of the rest, ensuring your table is always up-to-date.

## Customization

The plugin is designed for flexibility. Users can customize the source URL for the Excel data, adjust the styling of the displayed table, or modify the logic for processing the Excel file as needed.

## Development

This plugin welcomes contributions from the community. To develop or contribute:

1. Clone the repository in GitHub Codespaces or your local development environment.
2. Make your changes, test thoroughly.
3. Submit a pull request with a clear description of your changes or enhancements.

## Requirements

- WordPress 5.0+
- PHP 7.4+

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

- Special thanks to all contributors who help maintain and enhance this project.
- Acknowledgment to data providers or any third-party services integrated with this plugin.
