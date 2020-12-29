FROM php:alpine
COPY main.php /
ENV OUTPUT_FILE ""
ENV OUTPUT_FORMAT "json"
VOLUME ["/data", "/output"]
CMD ["php", "/main.php"]
