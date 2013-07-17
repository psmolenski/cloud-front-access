#CloudFrontAccess
Sample code that shows differences between accessing AWS CloudFront Service using either: official AWS PHP SDK or a dedicated custom class.

##Project content
In repository you will find 2 runnable files:

* `src/CloudFrontClient.php` - contains sample code athat connects to CloudFront API using a **dedicated custom class** (depends only on cURL) - total 8 KB of code
* `src/sdk.php` - contains sample code that connects to CloudFront API using the official **AWS PHP SDK** (make sure you follow the SDK installation instructions below) - total 8 MB of code

##Configuration (both `sdk.php` and `CloudFrontClient.php`)
To connect to AWS CloudFront service you have to provide credentials:

* Access key ID,
* Secret key ID.

Both sdk.php and CloudFrontClient are expectig to find those values in eviroment variables called:

* ACCESS_KEY_ID
* SECRET_KEY_ID

so be sure to provide them before running the code.

**Notice**: If you provide the correct enviroment variables, but you still keep getting an error chances are, that the problem lays in your PHP configuration.
Be sure to set the `variables_order` setting to value `EGPCS`.

##Running `CloudFrontClient.php`

    $ php CloudFrontClient.php

Code in this file lists all available distributions by default, but can also:

* list invalidations for given distribution,

* show details for given invalidation,

* add new invalidation for resources in given distributions.

To use those additional functions you have to uncomment apropriate lines at the end of the file and provide required arguments.

All responses from server are presented as raw XML.

##Running `sdk.php`

    $ php sdk.php

Code in this file lists all available distributions.

##AWS PHP SDK installation
In order to run the code from `sdk.php` you have to install the official AWS PHP SDK in 3 simple steps:

1. Go to directory where `composer.json` file is placed.

1. Install Composer with command:
`curl -sS https://getcomposer.org/installer | php`

    **Note**: If you install Composer for the first time you might need to change your PHP configuration.
The installer will give you information about settings that need to be altered.

1. Install SDK with dependencies
`php composer.phar install`

That's all. After performing those operations you should end up with new directory "vendor" where resides the SDK.

##References
* [AWS PHP SDK](http://aws.amazon.com/sdkforphp/)
* [CloudFront API](http://docs.aws.amazon.com/aws-sdk-php-2/latest/namespace-Aws.CloudFront.html)
* [Composer](http://getcomposer.org/) - Dendency Manager for PHP
