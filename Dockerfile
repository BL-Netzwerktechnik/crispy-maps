ARG CRISPY_VERSION=2.5.1

FROM registry.jrbit.de/jrb-it/crispy:$CRISPY_VERSION


ARG MAPS_THEME_GIT_COMMIT=NF_HASH
ARG MAPS_THEME_GIT_TAG=nightly


ENV MAPS_THEME_GIT_COMMIT "$MAPS_THEME_GIT_COMMIT"
ENV MAPS_THEME_GIT_TAG "$MAPS_THEME_GIT_TAG"
ENV AUTO_ACTIVATE_PLUGINS=maps


COPY --chown=33:33 plugin /plugins/maps
COPY --chown=33:33 assets /assets
COPY nginx/assets.conf /etc/nginx/crisp.conf.d/maps_assets.conf

RUN rm -rf /plugins/hello-world

RUN cd /plugins/maps && composer install