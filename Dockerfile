ARG CRISP_VERSION=19

FROM registry.jrbit.de/crispcms/core/licensed:$CRISP_VERSION

ARG THEME_GIT_COMMIT=NF_HASH
ARG THEME_GIT_TAG=nightly


ENV THEME_GIT_COMMIT "$THEME_GIT_COMMIT"
ENV THEME_GIT_TAG "$THEME_GIT_TAG"

ENV DEFAULT_LOCALE "en"
ENV LANG "en_US.UTF-8"

ENV LICENSE_SERVER "https://prod.activation.pixelcowboys.de/validate/v1?key={{key}}&instance={{instance}}"

COPY --chown=33:33 public /var/www/crisp/cms/themes/crisptheme

COPY --chown=33:33 plugins /plugins


COPY nginx/ugc.conf /etc/nginx/crisp.conf.d/ugc.conf

RUN cd /plugins && ./install.sh && cd /var/www/crisp/cms/themes/crisptheme/includes && composer install
