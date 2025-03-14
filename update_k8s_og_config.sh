kubectl create configmap $CLIENT_NAME-og-secret-keys --dry-run -o yaml --from-file=og_config/$CLIENT_NAME/og/secret_keys | kubectl replace -f -
kubectl create configmap $CLIENT_NAME-og-config-local --dry-run -o yaml --from-file=og_config/$CLIENT_NAME/og/config_local | kubectl replace -f -
