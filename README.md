# Hypertext Application Language (HAL) for PSR-7 Applications

> ## ABANDONED
>
> Please use the package [zendframework/zend-expressive-hal](https://github.com/zendframework/zend-expressive-hal)
> instead, as development has moved to that repository.

[![Build Status](https://secure.travis-ci.org/weierophinney/hal.svg?branch=master)](https://secure.travis-ci.org/weierophinney/hal)
[![Coverage Status](https://coveralls.io/repos/github/weierophinney/hal/badge.svg?branch=master)](https://coveralls.io/github/weierophinney/hal?branch=master)

This library provides provides utilities for modeling HAL resources with links
and generating [PSR-7](http://www.php-fig.org/psr/psr-7/) responses representing
both JSON and XML serializations of them.

## Installation

Run the following to install this library:

```bash
$ composer require weierophinney/hal
```

## Documentation

Documentation is [in the doc tree](doc/book/), and can be compiled using [mkdocs](http://www.mkdocs.org):

```bash
$ mkdocs build
```

You may also [browse the documentation online](https://weierophinney.github.io/hal/index.html).
