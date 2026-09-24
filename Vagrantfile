Vagrant.configure("2") do |config|

  config.vm.box = "ubuntu/jammy64"
  config.vm.boot_timeout = 1200
  config.vm.hostname = "northenbridge"

  # Phase 3 — Provisioning-Only Build.
  # No shared folders: the whole CTF is built inside the VM by
  # infra/provision.sh (portal source is cloned from GitHub into
  # /var/www/html). Nothing is read from /vagrant at runtime.
  config.vm.synced_folder ".", "/vagrant", disabled: true
  config.vm.synced_folder "./www", "/var/www/northenbridge", disabled: true

  # Access the site from the host browser: http://localhost:8080
  config.vm.network "forwarded_port", guest: 80, host: 8080, host_ip: "127.0.0.1"

  # Access the site from other devices on the same LAN (bridged):
  # http://<VM-IP>/
  config.vm.network "public_network"

  config.vm.provider "virtualbox" do |vb|
    vb.name = "northenbridge-ctf"
    vb.memory = 2048
    vb.cpus = 2
  end

  # Set PORTAL_REPO (and optionally PORTAL_BRANCH) in the host shell
  # before `vagrant provision` to deploy from a different repository:
  #
  #   $env:PORTAL_REPO = "http://10.0.2.2:9418/repo"
  #   vagrant provision
  config.vm.provision "shell",
                      path: "infra/provision.sh",
                      env: {
                        "PORTAL_REPO"   => ENV["PORTAL_REPO"]   || "",
                        "PORTAL_BRANCH" => ENV["PORTAL_BRANCH"] || ""
                      }

end