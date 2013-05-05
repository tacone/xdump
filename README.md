X-Dump
=========

A little PHP library I wrote **many** years ago that dumps PHP variables in a readable/contractible format.

I'll keep it here for my own use. I won't be developed further because nowadays there's much better stuff 
around and also because I lost all my unit-tests :(

**Project URL: [https://github.com/tacone/xdump/](https://github.com/tacone/xdump/)**

Use
====

Use it like this:

```php
require('xdump.php');

//single dump
echo xdump::dump($var);
echo xdump::dump($array, "Long query result");

//multiple dump
echo xdump::mdump($var1, $var2, $var3);

//debug backtrace
echo xdump::backtrace();
```
