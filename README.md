# WebbyPHP Engine

<p align="center">
    <img src="webbyphp.png" width="600" alt="WebbyPHP">
</p>

The engine repo for WebbyPHP Framework Core extended functionalites for the CodeIgniter 3 framework

![MIT License](https://img.shields.io/github/license/webbyphp/webby)
![Lines of code](https://img.shields.io/tokei/lines/github/webbyphp/engine) ![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/webbyphp/webby) ![Packagist Version](https://img.shields.io/packagist/v/webbyphp/engine) ![Packagist Downloads (custom server)](https://img.shields.io/packagist/dt/webbyphp/engine)

## About WebbyPHP

**WebbyPHP** aims to be a "lego-like" PHP framework that allows you to build APIs, Console/Cli and Web Applications in a modular architecture, that can also integrate other features from existing PHP frameworks or other PHP packages with ease. 

It is an extension of the CodeIgniter 3 framework for easy web application development with an easy developer experience (DX) for beginners.

Build Awesome PHP applications with a "Simple(Sweet) Application Architecture". 

## Features

- Easy and Improved Routing
- HMVC First Architecture
- Application can be APIs, Console or Web Based
- Easy to integrate with Other Frameworks
- Extend with Packages
- Use "Plates" a blade-like templating engine for your views
- Use "Services" to seperate business logic from Controllers
- Use "Actions" instead of "Services" for CRUD functionalities or business logic
- Use "Forms Or Rules" to validate input requests
- A near "Service discovery" feature included
- Use any database abstraction or library you want as a model

## Authors

- [@otengkwame](https://www.github.com/otengkwame)
- [All Contributors][link-contributors]

## Installation

The recommended way to install Webby is [through Composer](https://getcomposer.org/).
Are you [New to Composer?](https://getcomposer.org/doc/00-intro.md) click on the link.

This will install the latest PHP supported version:

```bash
$ composer create-project webbyphp/webby <project-name>
```

Make sure to replace *project-name* with the name of your project

## Documentation

The main documentation of WebbyPHP can be found here: [WebbyPHP Docs](https://webbyphp.top/docs)

The documentation is currently been updated constantly. It will take time to cover all aspects of the framework but we are working around the clock to make this possible. 

Currently we have planned to use the blogs section to guide developers through their journey in learning the framework.

If you have been developing with CodeIgniter 3 already and you are familiar with the HMVC approach you can still use the same knowlegde to get going.

For developers who are very familiar with the CodeIgniter 3 framework can still refer to the documentation here: [CI3 Docs](https://www.codeigniter.com/userguide3/index.html)

The concept of CodeIgniter 4 has not been so clear and rewriting CodeIgniter 3 has set the framework back in so many ways, this is a way to show that Codeigniter could have been improved gradually without the approach the Core Team 
used.

## Folder Structure

```
vendor/
└── webbyphp/
    └── engine/
        └── CodeIgniter/
            └── Framework/
                ├── core/
                ├── database/
                ├── fonts/
                ├── helpers/
                ├── language/
                └── libraries/
        └── Core/
            ├── config/
            ├── controllers/
            ├── core/
            ├── helpers/
            ├── hooks/
            ├── language/
            ├── libraries/
            ├── logs/
            └── models/

```

## Server Requirements

PHP version 8.3 or newer is recommended.

PHP 8.1 was released in November 2021 and so most of it's functionalities were not known to be supported yet, this delayed the development of this project to work perfectly with the latest version 8.1 of PHP and the framework, ~~we advise to stay between versions 7.4 and 8.0 for stable PHP appplication development.~~ 

~~If you want to discover bugs and contribute, then you are welcome to use the PHP 8.1 version.~~

Currently it supports 8.5 with no issues coming up yet. All issues can be discussed and it will be addressed. PHP 8.5 is here since 20th November 2025. We will be looking forward to related issues too to resolve. Currently we are ready for bug hunting. Let's go :).


## Quick FAQs

#### Why did you decide to create Webby
---
* Webby was created with PHP beginners in mind, to simplify how web applications can be built (with PHP) without complex concepts and functionalities
* Looking at how other (PHP) frameworks makes it difficult for beginners to start, we are making the approach different. 
* Also CodeIgniter 3 was not been updated for sometime and new PHP versions were not working until they updated to the recent version (3.1.13).
* I used it as an opportunity to learn and understand more about Software Architecture and creating Software Paradigms.

#### Is it anything different from CodeIgniter 3 or 4?
---
It uses the Core of the CodeIgniter 3 framework and borrows some new features added from CodeIgniter 4. It is designed to move developers who are familiar with CI3 to easily adapt to CI4 with a little similar syntax or concept.


## Important Links

The links below will guide you to know more about how Webby Works

* [Installation Guide](https://webbyphp.top/docs/installation/)
* [Getting Started](https://webbyphp.top/docs/getting-started/)
* [Contribution Guide](https://webbyphp.top/docs/contribution-guide/)
* [Learn Webby](https://blog.webbyphp.top)
* [Community](https://github.com/webbyphp/webby/discussions)

## What's Next
There are lots of future plans for WebbyPHP

* [x] Enable and Test for PHP 8.1 compatibility
* [x] Write version two (v2) without a major class api change so as to reduce future upgrade headache. Unlike other major PHP Frameworks
* [x] Improve and simplify CI3's database migrations
* [x] Enable module based packages to use composer packages
* [x] Enable easy engine folder upgrade (Currently folder will have to be replaced when an update is available) (Done on 30th October 2022 18:22 PM)
* [x] Move sylynder/codeigniter repo to sylynder/engine repo (Done on 31st December 2022 15:08 PM)
* [x] Move sylynder/engine repo to webbyphp/repo (Done on November 2025)
* [-] Write version three (v3) to support PHP8.5
* [x] Make version three compatible with legacy CodeIgniter 3 projects
* [ ] Create composer ready repo for beginner developers with difficulties 
* [ ] Build a WHATstack starter kit
* [ ] Build an HTMX starter kit
* [ ] Build an AlpineJS starter kit
* [ ] Build a package manager feature
* [ ] Create a compatible HTTP and Routing feature (may be PSR-7 compatible)  that enables general integration with other frameworks
* [ ] Improve and optimize speed
* [ ] Improve on cli or console feature
* [ ] Integrate asynchronous features (may be fibers) [as a package]
* [ ] And many more to add (and many more to learn)


## Credits

- Rougin (https://github.com/rougin/spark-plug)
- Yidas (https://github.com/yidas/codeigniter-rest)
- Chriskacerguis (https://github.com/chriskacerguis/codeigniter-restserver)
- Nobitadore (https://github.com/nobitadore/Arrayz)
- Lonnieezell (https://github.com/lonnieezell/Bonfire)
- GustMartins (https://github.com/GustMartins/Slice-Library)
- CodeIgniter 3 (https://github.com/bcit-ci/CodeIgniter)
- CodeIgniter 4 (https://github.com/codeigniter4/CodeIgniter4)
- Laravel Blade - Plates Template Engine Idea (https://laravel.com/docs/12.x/blade) 
- Laravel Collections - Arrayz Collection Idea (https://laravel.com/docs/12.x/collections)
- Laravel Routing - (https://laravel.com/docs/12.x/routing)
- [All Contributors][link-contributors]


## License

We are using the MIT License (MIT). Please see our LICENSE.md file. If you want to know more about the license go to [LICENSE]((https://choosealicense.com/licenses/mit/)) for more information.

[link-contributors]: https://github.com/webbyphp/engine/contributors
