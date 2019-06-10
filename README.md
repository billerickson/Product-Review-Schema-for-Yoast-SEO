# Product Review Schema for Yoast SEO

Yoast SEO uses the `Article` schema type for all posts. This plugin adds a "Product Review" box below the content editor for marking this as a review, and specifying the review information (product name, rating, summary...).

For more information, see: https://www.billerickson.net/product-review-schema-for-yoast-seo/

## Screenshots

![screenshot](https://www.billerickson.net/wp-content/uploads/2019/06/review-schema-featured.jpg)
Screenshot from [Google Structured Data Testing](https://search.google.com/structured-data/testing-tool/u/0/) tool showing Review schema being used on this post

![screenshot](https://d3vv6lp55qjaqc.cloudfront.net/items/1V3O3q1Q241g0Z372j1P/Screen%20Shot%202019-06-10%20at%202.50.23%20PM.png?X-CloudApp-Visitor-Id=78955b2d79e4b4c9650076a91b4db727&v=818d85dc)
Product Review metabox appears below the content editor

## Filters
- `be_product_review_schema_post_types` - Customize which post types can appear as reviews. Default is `array( 'post' )`
- `be_product_review_schema_metabox` - Whether or not the Product Review metabox should be added. You could build your own custom metabox with the same meta keys, and disable this one using `add_filter( 'be_product_review_schema_metabox', '__return_false' )`
- `be_review_schema_data` - Customize the schema data added for reviews
