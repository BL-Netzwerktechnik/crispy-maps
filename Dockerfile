FROM registry.jrbit.de/jrb-it/crispy:stable

COPY --chown=33:33 plugin /plugins/lostplaces

RUN cd /plugins/lostplaces && composer install