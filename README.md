wp-get-archives-with-image
==========================

**phpdoc is notyet!**


## Usage 

```
function wp_get_archives_image( mixed $args )
```

## params.
`$args`: query string or array.
type, limit, before, after, show_post_count, echo and order are same as `wp_get_archives()`.

format has only 2 pattern : html and custom.

**require**: You have to set `html` param :

```ex
'html' => '<img src="http://example.com/img/foo%1$d.png" alt="%2$s" />',
```

`%1$d` will replace to intiger of selected day,month,year or week and `%2$s`will replace to name of that(i18n formatted).


## default usage

```php
$args = array(

		'type' => 'monthly', 

    'limit' => '',

		'format' => 'html', 

    'before' => '',

		'after' => '', 

    'show_post_count' => false,

		'echo' => 1, 

    'order' => 'DESC',
		
		'html' => '<img src="%1$s" alt="%2$s">', 
		

	);
```

## Examples

```php
wp_get_archives_image(
 array(
 'type'=>'yearly',
 'limit'=>10,
 'format'=>'custom',
 'html'=>'<img src="http://example.com/images/hoge.jpg" width="100" alt="%2$s" />'
  )
 );
```



@todo

## Attention!!

*If you use this function in your plugins or themes, you must add the plefix!*