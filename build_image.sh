PROJECT_NAME=$(kubectl describe deploy `kubectl get deploy | grep og | tail -n 1 | awk '{print $1}'` | grep mage | cut -d "/" -f 2)
echo "$PROJECT_NAME"
echo "$GITLAB_USER_EMAIL build baseimage"
docker build -t ogbasephp:latest baseimage
echo "$GITLAB_USER_EMAIL build $CI_COMMIT_REF_NAME on $PROJECT_NAME"
# - gcloud docker -- pull asia.gcr.io/$GOOGLE_PROJECT_NAME/ogbasephp:latest-live
docker tag asia.gcr.io/$PROJECT_NAME/ogbasephp:latest-live ogbasephp:latest
bash sync_submodule.sh
echo -n $CI_COMMIT_SHA > admin/public/version
docker build -t og:$CI_COMMIT_REF_NAME .
docker tag og:$CI_COMMIT_REF_NAME asia.gcr.io/$PROJECT_NAME/og:$CI_COMMIT_REF_NAME
docker tag og:$CI_COMMIT_REF_NAME asia.gcr.io/$PROJECT_NAME/og:$CI_COMMIT_REF_NAME-$CI_COMMIT_SHA
gcloud docker -- push asia.gcr.io/$PROJECT_NAME/og:$CI_COMMIT_REF_NAME
gcloud docker -- push asia.gcr.io/$PROJECT_NAME/og:$CI_COMMIT_REF_NAME-$CI_COMMIT_SHA
