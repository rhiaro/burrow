# Burrow

A checkin client that posts to a server using the ActivityPub client-to-server protocol... Sort of. 

It sends an `Arrive` activity with `location` and `published` with Content-Type `activity/ld+json`. Auth just uses the `Authorization` header for now.

```
docker run --rm --interactive --tty \
  --volume $PWD:/app \
  --volume ${COMPOSER_HOME:-$HOME/.composer}:/tmp \
  composer install
```