touch /tmp/dependancy_dotti_in_progress
echo 0 > /tmp/dependancy_dotti_in_progress
echo "Launch install of Dotti dependancy"
sudo apt-get update
echo 50 > /tmp/dependancy_dotti_in_progress
sudo apt-get install -y bluez bluez-hcidump 
echo 100 > /tmp/dependancy_dotti_in_progress
echo "Everything is successfully installed!"
rm /tmp/dependancy_dotti_in_progress