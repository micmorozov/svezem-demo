FROM ubuntu
RUN apt-get update
RUN apt-get install sphinxsearch -y\
    mysql-client -y

COPY ./daemon.sh ./daemon.sh
RUN chmod +x daemon.sh
CMD ./daemon.sh
