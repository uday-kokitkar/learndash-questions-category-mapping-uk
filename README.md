# LearnDash Questions Category Mapping

A WordPress plugin that enables you to map LearnDash quiz question categories to specific course lessons and topics, providing personalized learning recommendations to students based on their quiz performance.

## Description

This plugin extends LearnDash LMS by allowing administrators to:

- Map quiz question categories to specific course lessons or topics
- Display recommended learning content on quiz results pages
- Help students identify areas for improvement with direct links to relevant course content
- Highlight categories where students scored below average (below 80%)

## Features

- **Admin Category Mapping Interface**: Easy-to-use interface for mapping question categories to course content
- **Smart Recommendations**: Automatically displays recommended lessons/topics based on quiz performance
- **Admin Bar Integration**: Quick access to category mappings from quiz edit screens
- **Quiz Row Actions**: Direct link to category mappings from the quiz list table
- **Caching**: Built-in caching for optimal performance
- **Filters**: Filter categories by assigned/unassigned status
- **Search**: Search functionality for easy category management

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- LearnDash LMS 4.0 or higher

## Installation

1. Upload the plugin files to the `/wp-content/plugins/learndash-questions-category-mapping-uk` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Questions > Category Mapping to start mapping categories

## Usage

### Mapping Categories to Course Content

1. Go to **Questions > Category Mapping** in your WordPress admin
2. Select a quiz (optional) to filter categories for that specific quiz
3. Click **Edit** on any category row
4. Select the **Course**, **Lesson**, and optionally a **Topic**
5. Click **Save**

### Accessing Mappings

- From the quiz list, click **Category Mapping** in the row actions
- From the quiz edit screen, click **Category Mapping** in the admin bar
- Filter by quiz using the `quiz_id` parameter in the URL

### For Students

After completing a quiz, students will see:
- Links to recommended content for categories where they need improvement
- Categories with scores below 80% highlighted in red
- Direct access to relevant lessons/topics

## Hooks & Filters

### Actions

- `ldqcm_cat_mapping_column_action_content` - Add custom content to the category mapping action column

## Changelog

### 1.0.0
- Initial release
- Category mapping functionality
- Admin interface
- Frontend recommendations
- Caching system

## Developer

**Uday Kokitkar**  
Website: [https://udayk.net/](https://udayk.net/)  
Email: uday.webdeveloper@gmail.com

## Support

For support, please contact the plugin developer.

## License

GPL v2 or later
