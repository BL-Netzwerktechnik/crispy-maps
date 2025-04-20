FROM registry.jrbit.de/jrb-it/crispy:stable

COPY --chown=33:33 plugin /plugins/lostplaces
COPY --chown=33:33 assets /data/files/assets

RUN cd /plugins/lostplaces && composer install