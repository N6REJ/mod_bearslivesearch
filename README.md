# Bears Live Search Module for Joomla 5

## What is Bears Live Search?
Bears Live Search is a modern, fully accessible Joomla 5 module that provides a lightning-fast AJAX search experience for your website visitors. Built with accessibility-first design principles, it delivers live search results as users type while maintaining excellent performance and user experience across all devices.

## Key Features
- **🚀 Lightning Fast**: AJAX-powered live search with instant results
- **♿ Fully Accessible**: WCAG compliant with skip links, ARIA attributes, and screen reader support
- **📱 Responsive Design**: Works perfectly on all devices and screen sizes
- **🎨 Highly Customizable**: Extensive styling and positioning options
- **🔍 Advanced Filtering**: Filter by category, author, date range, and search criteria
- **📊 Smart Results**: Configurable character limits and pagination
- **🎯 Flexible Display**: Inline results or separate page transformation
- **🔧 Developer Friendly**: Clean code, comprehensive language strings, and extensible architecture

## What's New in Latest Version

### Major Improvements
- **Simplified Width Control**: Streamlined from dual width/max-width to single width parameter for easier configuration
- **Enhanced Results Display**: Search results now use 100% width while form maintains configured width
- **Improved Accessibility**: Enhanced ARIA labels, skip links, and screen reader compatibility
- **Better Language Support**: Comprehensive language string organization with 67+ translatable strings
- **Optimized CSS Architecture**: Cleaner CSS with better specificity and maintainability

### Technical Enhancements
- **Form-Only Width Control**: Width setting now applies only to search form, results use full container width
- **Improved Positioning**: Better left/right/center positioning with proper float handling
- **Enhanced Border Controls**: Comprehensive border radius, size, and color customization
- **Icon Flexibility**: Support for Joomla icons, FontAwesome, or no icons
- **Search Mode Options**: Choose between inline results or page transformation

## Module Settings Explained

### Basic Configuration
- **Results Character Limit**: Maximum characters shown per result (default: `300`)
- **Results Per Page**: Number of results to display (default: `10`, range: 10-200)
- **Show Search Criteria**: When to display search filters (`Always` or `After search is started`)
- **Search Results Display**: Choose `Inline` (below form) or `Separate Page` (transforms page)

### Layout & Positioning
- **Module Position**: Float module `Left`, `Right`, or `Center`
- **Form Width**: Width of search form only - results use full width (default: `50%`)
- **Input Form Margin**: CSS margin for search form (default: `1em 0`)
- **Output Form Margin**: CSS margin for results container (default: `1em 0`)
- **Module Margin**: CSS margin for entire module container (default: `0 auto`)
- **End Position**: Where to display results in separate page mode

### Styling Options
- **Border Radius**: CSS border radius for search form (e.g., `0`, `5px`, `1rem`)
- **Border Size**: Border width for search form (e.g., `1px`, `2px`)
- **Border Color**: Border color with color picker or `transparent` for no border
- **Search Icon**: Choose from `None`, `Joomla Icon`, or `FontAwesome Icon`

### Advanced Options
- **Module Class Suffix**: Add custom CSS classes for additional styling
- **Caching**: Enable/disable module caching for performance optimization
- **Cache Time**: Set cache duration in seconds

## Search Features

### Advanced Filtering
- **Search Criteria**: Any words, All words, or Exact phrase matching
- **Category Filter**: Filter results by specific content categories
- **Author Filter**: Filter by content author
- **Date Range**: Filter by publication date (from/to)
- **Sort Options**: Newest first, Oldest first, Most popular, or Alphabetical

### Results Display
- **Smart Truncation**: Results truncated at word boundaries with ellipsis
- **Pagination**: Navigate through multiple result pages
- **Live Updates**: Results update as you type without page refresh
- **Accessible Navigation**: Keyboard navigation and screen reader support

## Installation & Setup

### Requirements
- Joomla 5.0 or higher
- PHP 8.0 or higher
- Modern web browser with JavaScript enabled

### Installation Steps
1. Download the latest release from GitHub
2. Install via Joomla's Extension Manager (Upload Package File)
3. Navigate to System → Manage → Modules
4. Create new Bears Live Search module or edit existing one
5. Configure settings and assign to desired module position
6. Publish the module

### Quick Start Configuration
1. **Basic Setup**: Set Results Per Page and Character Limit
2. **Positioning**: Choose module position (Left/Right/Center)
3. **Width**: Set form width (e.g., `400px`, `50%`, `30rem`)
4. **Display Mode**: Choose Inline or Separate Page results
5. **Styling**: Configure borders, icons, and margins as needed

## CSS Classes & Customization

### Main Classes
- `.bearslivesearch` - Main module container
- `.bearslivesearch-form` - Search form container
- `.bearslivesearch-results` - Results container
- `.bearslivesearch-list` - Results list
- `.bearslivesearch-loading` - Loading indicator

### Positioning Classes
- `.bearslivesearch-float-left` - Left-aligned module
- `.bearslivesearch-float-right` - Right-aligned module
- `.bearslivesearch-float-none` - Center-aligned module

### State Classes
- `.bearslivesearch-results--hidden` - Hidden results container
- `.bearslivesearch-criteria-hidden` - Hidden search criteria

## Accessibility Features

### WCAG Compliance
- **Skip Links**: Direct navigation to search results
- **ARIA Labels**: Comprehensive labeling for screen readers
- **Keyboard Navigation**: Full keyboard accessibility
- **Focus Management**: Proper focus indicators and management
- **Semantic HTML**: Proper heading structure and landmarks

### Screen Reader Support
- **Live Regions**: Results announced as they update
- **Form Labels**: All inputs properly labeled
- **Status Messages**: Loading and error states announced
- **Navigation Aids**: Clear navigation structure

## Developer Information

### File Structure
```
mod_bearslivesearch/
├── mod_bearslivesearch.php          # Main module file
├── mod_bearslivesearch.xml          # Module configuration
├── helper.php                       # Search logic and AJAX handling
├── tmpl/default.php                 # Template file
├── media/
│   ├── css/bearslivesearch.css     # Module styles
│   └── js/bearslivesearch.js       # AJAX functionality
├── fields/endpositions.php         # Custom field for position selection
└── language/en-GB/                 # Language files
    ├── en-GB.mod_bearslivesearch.ini
    └── en-GB.mod_bearslivesearch.sys.ini
```

### Language Support
- **67+ Language Strings**: Fully translatable interface
- **Organized Categories**: Logical grouping of translation strings
- **Core Overrides**: Joomla core strings overridden for consistency
- **Developer Friendly**: Clear naming conventions and documentation

### Customization
- **CSS Variables**: Use CSS custom properties for easy theming
- **Module Class Suffix**: Add custom classes for specific styling
- **Template Overrides**: Override templates in your theme
- **Hook System**: Extensible architecture for developers

## Browser Support
- **Modern Browsers**: Chrome, Firefox, Safari, Edge (latest versions)
- **Mobile Browsers**: iOS Safari, Chrome Mobile, Samsung Internet
- **Accessibility Tools**: NVDA, JAWS, VoiceOver compatible
- **Progressive Enhancement**: Works without JavaScript (basic functionality)

## Performance
- **Optimized AJAX**: Efficient search queries with minimal server load
- **Caching Support**: Built-in caching for improved performance
- **Lazy Loading**: Results loaded on demand
- **Minimal Footprint**: Lightweight CSS and JavaScript

## Troubleshooting

### Common Issues
1. **No Results Appearing**: Check Joomla search plugins are enabled
2. **Styling Issues**: Verify CSS is loading and check for theme conflicts
3. **AJAX Not Working**: Ensure JavaScript is enabled and check browser console
4. **Width Not Applied**: Clear cache and check CSS specificity

### Debug Mode
Enable Joomla debug mode to see detailed error messages and performance information.

## Support & Contributing

### Getting Help
- **Documentation**: [https://hallhome.us/software](https://hallhome.us/joomla-extensions/bears-live-search))
- **Issues**: [GitHub Issues](https://github.com/N6REJ/mod_bearslivesearch/issues)
- **Discussions**: [GitHub Discussions](https://github.com/N6REJ/mod_bearslivesearch/discussions)

### Contributing
- **Bug Reports**: Use GitHub Issues with detailed reproduction steps
- **Feature Requests**: Submit via GitHub Issues with use case description
- **Pull Requests**: Follow coding standards and include tests
- **Translations**: Help translate the module into other languages

## License
GNU General Public License version 3 or later. See LICENSE.txt for details.

## Credits
Developed by N6REJ (Troy Hall) with focus on accessibility, performance, and user experience.

---

**Version**: 2025.07.19.44  
**Joomla Compatibility**: 5.0+  
**PHP Compatibility**: 8.0+  
**Last Updated**: January 2025
