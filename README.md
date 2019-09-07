# Traffic

## Usage and initialization
### Renaming
- ***plugin_name***: the name of the plugin, in human-readable format.
- ***plugin_slug***: the slug of the plugin, must be spinal case.
- ***plugin_class***: the class base name, basically the ***plugin_slug*** in upper snake case.
- ***plugin_namespace***: the namespace base for the plugin.
- ***plugin_acronym***: the acronym of the plugin.
#### Files
The file containing the following strings in their names must be renamed:
- `traffic` must be replaced by `plugin-slug`
#### Strings
The following strings in files must be globally renamed:
- `traffic` to ***plugin-slug***
- `Traffic` to ***plugin_name***
- `Traffic` to ***plugin_class***
- `Traffic` to ***plugin_namespace***
- `traffic` to lowercase ***plugin_acronym***
- `TRAFFIC` to uppercase ***plugin_acronym***