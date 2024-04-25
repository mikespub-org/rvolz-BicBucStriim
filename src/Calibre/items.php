<?php
/**
 * Calibre DB items using PDO::FETCH_CLASS
 */

namespace BicBucStriim\Calibre;

# A database item class
#[\AllowDynamicProperties]
class Item {}
class ItemCount extends Item
{
    public $ctr;
}
# Utiliy classes for Calibre DB items
class Author extends Item
{
    public $sort;
    public $name;
    public $thumbnail;
    public $notes_source;
    public $notes;
    public $links;
}
class AuthorBook extends Item {}
class Book extends Item
{
    public $id;
    public $language;
    public $formats;
    public $addInfo;
}
class BookAuthorLink extends Item {}
class BooksCustomColumnLink extends Item {}
class BookSeriesLink extends Item {}
class BookTagLink extends Item {}
class BookLanguageLink extends Item {}
class Comment extends Item {}
class CustomColumn extends Item {}
class CustomColumns extends Item {}
class Data extends Item {}
class Initial extends Item
{
    public $initial;
    public $ctr;
}
class Language extends Item
{
    public $lang_code;
    public $key;
}
class Series extends Item {}
class SeriesBook extends Item {}
class Tag extends Item
{
    public $name;
    public $key;
}
class TagBook extends Item {}
class Identifier extends Item {}
