# B2CDN

A simple CDN object uploader for Backblaze B2 and other S3-compatible storage.

## Logic

Since the announcement of the [Cloudflare Bandwidth Alliance](https://blog.cloudflare.com/bandwidth-alliance/), outgoing traffic is now free from Backblaze B2 if your domain passes through the Cloudflare network. Since uploading is already free (minus the storage costs, of course), this makes for a perfect CDN.

However, there is no standard Backblaze B2 PHP SDK and using most of them resulted in uncaught exceptions. Enter [*Minio*](https://minio.io), an S3-compatible object storage gateway written in Go. It supports B2 as a gateway and can run through a single binary.

B2CDN leverages S3-compatible storage to talk to Backblaze B2's API. For instance, to manage an object upload:

```
B2CDN -> Minio -> Backblaze B2 -> B2CDN (result)
```

1. The file gets retrieved by B2CDN.
2. The Amazon PHP SDK uploads it to Minio, an S3-compatible gateway.
3. Backblaze B2 stores the object in a public bucket.
4. B2CDN returns a JSON response containing the status of the request and the URL to your file.

Your URL must pass through Cloudflare's network for traffic to be free. For instructions on how to do that, this article from [Backblaze](https://help.backblaze.com/hc/en-us/articles/217666928-Using-Backblaze-B2-with-the-Cloudflare-CDN) can help you get started.

### Sample Response
```
{
    "status":201,
    "message":"File created.",
    "url":"https:\/\/cdn.undernet.space\/file\/undernet-cdn\/testfile.zip"
}
```

The proper HTTP response codes are also sent.

## Usage

B2CDN works as a web-accessible API endpoint or a CLI-based utility. If B2CDN is ran through a webserver, it returns a JSON response. If it is ran using the CLI, it displays the progress on-demand.

On tests, this consumes **5** Class C API calls on Backblaze B2 for every upload. Be mindful of your API calls, as this may cost you unexpected charges.

### Web API Deployment

Simply download/clone this package, run `composer install` to grab the dependencies and edit `config.sample.php` to your liking and rename it to `config.php`.

Then for your API requests, simply submit a `GET` request to the endpoint with these two parameters:

* `source` - The source file. For example, `https://demo.com/test.js`
* `dest` - The destination. This is the path relative to your bucket's root. For example, `libraries/jquery/jquery.min.js`. There is no need to put a forward slash at the beginning. Directories are automatically created on the remote.

### CLI Usage

Simply download/clone this package, run `compoesr install` to grab the dependencies and edit `config.sample.php` to your liking and rename it to `config.php`.

Then, on your terminal, run:

```
php index.php <source> <destination>
```

Where:

* `source` - The source file. For example, `https://demo.com/test.js`
* `dest` - The destination. This is the path relative to your bucket's root. For example, `libraries/jquery/jquery.min.js`. There is no need to put a forward slash at the beginning. Directories are automatically created on the remote.

## Configuration Options

* `access_key` - Your Minio access key.
* `secret_key` - Your Minio secret key.
* `bucket` - Your bucket name on Backblaze B2. Make sure Minio has access to this.
* `endpoint` - The URL to your Minio installation.
* `proxy_url` - The URL of your CDN that passes through Cloudflare.

## Other usages

You can also use this script for other functions, like simple object dumping on S3 or S3-compatible object storage. This is simply a wrapper for `aws-php-sdk` with an API endpoint service capability.

## Demo

A demo will be up very soon.

## License & Contributing

This script is written and distributed under the [MIT Open Source license](https://opensource.org/licenses/MIT).

For contributions, simply fork, patch and submit a pull request. Make sure to follow the PSRs.