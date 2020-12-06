# Changelog

## 1.1.0 (2020-12-06)

*   Feature: Forward compatibility with stable EventLoop 1.0 and 0.5.
    (#11 by @clue)

*   Improve documentation and add API docs (docblocks).
    (#10 and #12 by @clue)

*   Improve test suite and add `.gitattributes` to exclude dev files from export.
    Update to PHPUnit 9 and simplify test setup.
    (#8, #9, #17 and #18 by @clue and #15 by @SimonFrings)

## 1.0.0 (2016-03-07)

* First stable release, now following SemVer
* Improved documentation

> Contains no other changes, so it's actually fully compatible with the v0.2.0 release.

## 0.2.0 (2015-03-26)

* Changed to use faster stream based networking API
  ([#6](https://github.com/clue/php-multicast-react/pull/6))
  * Reduce footprint of required dependencies
  * Only require `ext-sockets` for *listening* on multicast addresses 

## 0.1.0 (2015-03-24)

* First tagged release
