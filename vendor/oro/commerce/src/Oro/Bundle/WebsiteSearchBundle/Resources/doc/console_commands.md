Console commands
================

OroWebsiteSearchBundle provides commands to interact with search index.

oro:website-search:reindex
--------------------------

This command performs reindexation of entities.  It has two optional arguments that allows to reindex
only entities of specified type or website.

Reindexation itself might take a lot of time for big amount of data, so it would be good idea to run it by schedule
(f.e. once a day).

All entities reindexation:
```
> php app/console oro:website-search:reindex
Starting reindex task for all mapped entities
Total indexed items: 733
```

Certain website and entity reindexation:
```
> app/console oro:website-search:reindex --website-id 1 --class OroUserBundle:User

```
