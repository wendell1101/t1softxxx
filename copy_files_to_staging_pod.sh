#!/bin/bash
echo K8S_NAME:$K8S_NAME
echo CLIENT_NAME:${CLIENT_NAME}


case "$K8S_NAME" in
"t1tog" )
        gcloud config set account sbecloudflare1@gmail.com
        gcloud container clusters get-credentials t1tog --zone asia-east1-a --project externalgateway-167009
;;
"t1togstaging" )
	gcloud config set account sbecloudflare1@gmail.com
	gcloud container clusters get-credentials t1togstaging --zone asia-east1-b --project externalgateway-167009
;;
"kgvipen" )
        gcloud config set account sbecloudflare1@gmail.com
        gcloud container clusters get-credentials kgvipen --zone europe-west2-c --project ibetg-164502
;;
"kgvipenstaging" )
        gcloud config set account sbecloudflare1@gmail.com
        gcloud container clusters get-credentials kgvipenstaging --zone europe-west2-b --project ibetg-164502
;;
"t1togbr" )
	gcloud config set account sbecloudflare1@gmail.com
	gcloud container clusters get-credentials t1togbr --zone southamerica-east1-c --project externalgateway-167009
;;
"t1toguk" )
	gcloud config set account sbecloudflare1@gmail.com
	gcloud container clusters get-credentials t1toguk --zone europe-west2-c --project externalgateway-167009
;;
esac

for target_list in $(kubectl get pod | grep ${CLIENT_NAME}-og | awk '{print $1}')
    do
	echo "pod is $target_list"
	for brand_file in $(cat copy_file_list.txt)
            do
		echo "check $brand_file"
        php -l $brand_file
		echo "copy $brand_file to pod"
		kubectl cp $brand_file $target_list:/home/vagrant/Code/og/$brand_file
	    done
    done

