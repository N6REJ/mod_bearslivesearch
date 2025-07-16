# Bears Live Search

Bears Live Search is an AJAX-powered, accessibility-first live search module for Joomla 5. It leverages the Joomla Finder (Smart Search) index for fast, relevant results and is designed to be fully accessible to all users, including those using assistive technologies.

## Features
- **AJAX-powered live search**: Results update in real time as you type, with no page reloads.
- **Finder (Smart Search) integration**: Uses Joomla's advanced Finder index for accurate, fast searching across your site content.
- **Accessibility-first design**:
  - Semantic HTML5 structure
  - ARIA roles and attributes for screen readers
  - aria-live updates for dynamic results
  - aria-current and aria-labels on pagination controls
  - Visible :focus styles for all interactive elements (input, button, links)
  - Keyboard navigation support throughout
  - "Skip to results" link for keyboard users
- **Pagination**: Accessible pagination with ARIA support, including labels for First, Last, Next, and Previous, and aria-current for the current page.
- **Customizable**: Easily adjust input and output margins via module parameters.
- **Responsive design**: Works seamlessly on all devices and screen sizes.
- **Error handling**: User-friendly error and status messages, including AJAX/network errors and empty results.
- **Screen reader support**: All dynamic updates and navigation are announced to assistive technologies.
- **No images required**: Purely text-based, but can be extended to support images with alt text.

## Installation
1. Download the latest package from the releases.
2. Install via the Joomla 5 Extension Manager.
3. Configure the module as needed in the Joomla admin.

## Usage
- Publish the module in your desired template position.
- Optionally adjust input/output margins and result limits in the module settings.
- Users can search instantly, navigate results and pagination with keyboard, and benefit from full accessibility support.

## License
This project is licensed under the GNU General Public License v3.0 (GPL-3.0). See [License.txt](License.txt) for details.
