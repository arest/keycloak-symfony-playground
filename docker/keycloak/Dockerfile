FROM quay.io/keycloak/keycloak:26.1

# Install custom mapper provider
COPY mappers/vikunja-team-mapper.jar /opt/keycloak/providers/vikunja-team-mapper.jar

WORKDIR /opt/keycloak

ENTRYPOINT ["/opt/keycloak/bin/kc.sh"]
