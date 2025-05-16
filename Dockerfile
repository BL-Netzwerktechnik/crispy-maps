FROM registry.jrbit.de/jrb-it/crispy:stable

ENV AUTO_ACTIVATE_PLUGINS=lostplaces


COPY --chown=33:33 plugin /plugins/lostplaces
COPY --chown=33:33 assets /assets
COPY nginx/assets.conf /etc/nginx/crisp.conf.d/lostplaces_assets.conf

RUN rm -rf /plugins/hello-world

RUN cd /plugins/lostplaces && composer install