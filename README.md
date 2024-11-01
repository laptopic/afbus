example of how to include a library in composer.json
```json
{
	"repositories": [
		{
		"type": "git",
		"url": "https://github.com/laptopic/afbus"
		}
	],
	"require": {
		"laptopic/afbus": "dev-master"
	},
	"autoload": {
		"psr-4": {
			"laptopic\\afbus\\": "Afbus/"
	}
	}
}
```
