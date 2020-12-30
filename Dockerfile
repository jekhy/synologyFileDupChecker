FROM php:alpine
LABEL maintainer="info@jekhy.com"
COPY main.php /
ENV OUTPUT_FORMAT "json"
ENV OUTPUT_FILE ""
ENV OUTPUT_TABLE "synology_file_duplicates"
VOLUME ["/data", "/output"]
CMD ["php", "/main.php"]
