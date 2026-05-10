# tl;dr

1. add the following settings to your `.env` file

    ```.env
    # API KEY: 
    # see: https://cp.sitehost.nz/api/list-keys
    SS_SITEHOST_API_KEY="foo" 

    # CLIENT ID
    # see: https://cp.sitehost.nz/ (top-left of screen)
    SS_SITEHOST_CLIENT_ID=123 

    # SERVER ID
    # see: go to server list https://cp.sitehost.nz/servers/, click on server, 
    # you end up on https://cp.sitehost.nz/servers/manage/server/YYY - YYY is server name. 
    SS_SITEHOST_SERVER="bar"  


    # STACK NAME
    # see: go to the container list https://cp.sitehost.nz/cloud/containers, click on container, 
    # you end up on https://cp.sitehost.nz/cloud/manage-container/server/ch-cyp-cc/stack/ZZZ - ZZZ is stack / container name. 

    SS_SITEHOST_NAME="foobar" # see: 
    ```

2. to clear/purge the cache, run `/dev/?flush=all`

3. everytime you save **publish** a page, the cache will be cleared/purged.

4. to clear/purge the cache on other `dataobjects`, do this:

   ```yml
    # see https://docs.silverstripe.org/en/developer_guides/configuration/configuration/
    ---
    Name: sitehost-cache-purge-dataobjects
    After: 'framework'
    ---

    MyDataObject:
    extensions:
    - Sunnysideup\SitehostCachePurge\Extensions\SitehostWriteExtension
   ```
