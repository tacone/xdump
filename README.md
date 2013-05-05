X-Dump
=========

A little PHP library I wrote **many** years ago that dumps PHP variables in a readable/contractible format.

I'll keep it here for my own use. I won't be developed further because nowadays there's much better stuff 
around and also because I lost all my unit-tests :(

**Project URL: [https://github.com/tacone/xdump/](https://github.com/tacone/xdump/)**

## Features

- Dumps data in a expandable/contractible format
- Recursion checks
- Tries to minimize the HTML size impact
- Shows source code around the dump invocation
- Optional description 
- Backtrace dumping, with source code, params et all
- OOP, should be easy to extend
- Works on old PHP versions, even on PHP 4.

## Use

Use it like this:

```php
require('xdump.php');

//single dump
echo xdump::dump($_SERVER);
echo xdump::dump($_SERVER, "The $_SERVER contents"); //with description

//multiple dump
echo xdump::mdump($var1, $var2, $var3);

//debug backtrace
echo xdump::backtrace();
```
