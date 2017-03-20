PROGRESS_FILE=/tmp/dependancy_dotti_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "Launch install of Dotti dependancy"
sudo apt-get update
echo 50 > ${PROGRESS_FILE}
sudo apt-get install -y bluez bluez-hcidump 
echo 66 > ${PROGRESS_FILE}
sudo apt-get install -y libglib2.0-dev
echo 75 > ${PROGRESS_FILE}
sudo pip install bluepy
echo 100 > ${PROGRESS_FILE}
echo "Everything is successfully installed!"
rm ${PROGRESS_FILE}