PROJECT_NAME=$(kubectl describe deploy $(kubectl get deploy | grep og | tail -n 1 | awk '{print $1}') | grep mage | cut -d "/" -f 2)
echo "deploy $CI_COMMIT_REF_NAME"
echo "kubectl set image deployment/$CLIENT_NAME-og $CLIENT_NAME-og=asia.gcr.io/$PROJECT_NAME/og:$(echo Y | gcloud alpha container images list-tags asia.gcr.io/$PROJECT_NAME/og | grep $CI_COMMIT_REF_NAME,$CI_COMMIT_REF_NAME | awk '{print $2}' | awk -F, '{print $2}')"
kubectl set image deployment/$CLIENT_NAME-og $CLIENT_NAME-og=asia.gcr.io/$PROJECT_NAME/og:$(echo Y | gcloud alpha container images list-tags asia.gcr.io/$PROJECT_NAME/og | grep $CI_COMMIT_REF_NAME,$CI_COMMIT_REF_NAME | awk '{print $2}' | awk -F, '{print $2}')
echo "waiting status deployment/$CLIENT_NAME-og"
sleep 2
kubectl rollout status deployment/$CLIENT_NAME-og
