# Phase 5 : Infrastructure as Code

Cette phase couvre la gestion de l'infrastructure par le code,
avec Pulumi pour le provisioning et Ansible pour la configuration avancée.

> **Contexte Hostinger** : Hostinger n'a pas d'API de provisioning (pas de provider
> Pulumi/Terraform). Le VPS se créé manuellement via le hPanel. En revanche,
> **toute la configuration** (securite, Docker, K3s, déploiement) est automatisée
> avec Ansible. Pulumi est présente ici pour la culture et au cas ou tu migres
> vers un provider avec API (Hetzner, DigitalOcean, Scaleway).

---

## 5.1 Principes de l'IaC

### Pourquoi l'Infrastructure as Code.

L'IaC, c'est l'idée de traiter l'infrastructure (serveurs, réseaux, firewalls, DNS)
comme du code source : versionne dans Git, reproductible, et automatise.

| Approche manuelle      | Infrastructure as Code |
|------------------------|------------------------|
| Clics dans une console | Code versionne         |
| Non reproductible      | Reproductible a 100%   |
| Difficile a auditer    | Historique Git         |
| Erreurs humaines       | Automatise, teste      |
| Documentation obsolete | Code = documentation   |

**Exemple concret** : sans IaC, quand on réinstalle un VPS, on passe des heures
à refaire toute la config à la main en se demandant "est-ce que j'ai bien pensé
à tout ?". Avec IaC, une seule commande remet tout en place.

### Les 3 piliers

1. **Idempotence** : appliquer plusieurs fois = meme résultat.
   Si Docker est deja installe, le script ne le réinstalle pas.
2. **Déclaratif** : on décrit l'état souhaite ("je veux Docker installe"),
   pas les etapes ("télécharge le .deb, installe-le..."). L'outil se débrouille.
3. **Versionne** : tout dans Git, review possible, rollback facile.

### Les deux familles d'outils IaC

Il y a une distinction importante entre deux types d'outils :

|               | Provisioning                                       | Configuration                    |
|---------------|----------------------------------------------------|----------------------------------|
| **Fait quoi** | Cree les ressources cloud (serveurs, reseaux, DNS) | Configure les serveurs existants |
| **Exemples**  | Pulumi, Terraform, CloudFormation                  | Ansible, Chef, Puppet            |
| **Langage**   | TypeScript, HCL, YAML                              | YAML, Ruby                       |
| **Quand**     | Avant d'avoir un serveur                           | Apres avoir un serveur           |

**Dans notre cas Hostinger**, le provisioning (creation du VPS) est manuel via le hPanel.
On se concentre donc principalement sur la **configuration** avec Ansible.
Mais on documente aussi Pulumi pour la culture et pour une éventuelle migration.

---

## 5.2 Pulumi - Introduction

### Ce que fait Pulumi

Pulumi permet de décrire des ressources cloud (serveurs, réseaux, firewalls, DNS)
dans un **vrai langage de programmation** (TypeScript, Python, Go). Quand on lance
`pulumi up`, il compare l'etat actuel avec l'etat souhaite et cree/modifie/supprime
les ressources necessaires.

### Pourquoi Pulumi plutot que Terraform

|                 | Pulumi                                  | Terraform           |
|-----------------|-----------------------------------------|---------------------|
| **Langage**     | TypeScript, Python, Go (vrais langages) | HCL (langage dedie) |
| **Typage**      | Fort (erreurs a la compilation)         | Faible              |
| **Logique**     | Boucles, conditions, fonctions natives  | Langage limite      |
| **Ecosysteme**  | npm/pip (millions de packages)          | Registre Terraform  |
| **Multi-cloud** | Oui                                     | Oui                 |

> **Note** : Terraform (ou son fork open source OpenTofu) est plus répandu en entreprise.
> Pulumi est plus agréable a utiliser si on connait deja TypeScript ou Python.
> Les deux font la meme chose, c'est surtout une question de preference.

### Installation

```bash
# Linux
curl -fsSL https://get.pulumi.com | sh

# macOS
brew install pulumi

# Verifier
pulumi version
```

### Quand Pulumi est utile (et quand il ne l'est pas)

**Utile quand** le provider a une API :

- Hetzner Cloud, DigitalOcean, Scaleway, AWS, GCP, Azure
- On peut creer/detruire des serveurs en une commande

**Pas utile quand** le provider n'a pas d'API :

- **Hostinger** : pas de provider Pulumi, creation manuelle via hPanel
- Certains hébergeurs low-cost sans API

> **Pour Hostinger**, on saute donc les sections Pulumi en pratique.
> Elles sont documentées ici pour comprendre les concepts et pouvoir les
> appliquer si tu changes de provider ou si Hostinger ajoute une API.

---

## 5.3 Pulumi avec Hetzner Cloud (exemple de reference)

> Cette section montre un exemple complet avec Hetzner Cloud.
> Meme si tu ne l'utilises pas avec Hostinger, ca illustre comment
> fonctionne le provisioning IaC.

### Structure du projet

```
infra/pulumi/
├── Pulumi.yaml              # Metadata du projet
├── Pulumi.dev.yaml          # Variables pour la stack "dev"
├── Pulumi.production.yaml   # Variables pour la stack "production"
├── package.json
├── tsconfig.json
└── src/
    ├── index.ts             # Point d'entree (orchestre tout)
    ├── network.ts           # Creation du reseau prive
    ├── server.ts            # Creation du serveur
    ├── firewall.ts          # Regles de firewall
    └── dns.ts               # Enregistrements DNS (optionnel)
```

### Initialisation

```bash
cd infra/pulumi

# Creer le projet Pulumi (génère le squelette)
pulumi new typescript --name myapp-infra

# Installer le provider Hetzner
npm install @pulumi/hcloud

# Configurer le token API Hetzner (chiffre automatiquement par Pulumi)
pulumi config set hcloud:token XXXXX --secret

# Creer des stacks (= environnements)
# Chaque stack a son propre état et ses propres variables
pulumi stack init dev
pulumi stack init production
```

> **Qu'est-ce qu'une stack ?** C'est un environnement isolé. La stack "dev"
> créé un petit serveur de test, la stack "production" créé un serveur plus puissant.
> Meme code, variables différentes.

### Configuration par stack

```yaml
# Pulumi.yaml - metadata du projet
name: myapp-infra
runtime:
  name: nodejs
  options:
    typescript: true
description: Infrastructure pour MyApp
```

```yaml
# Pulumi.production.yaml - variables de la stack production
config:
  hcloud:token:
    secure: xxxxxxxxxx  # Chiffre automatiquement par Pulumi
  myapp-infra:environment: production
  myapp-infra:serverType: cx22       # 2 vCPU, 4 GB RAM
  myapp-infra:location: nbg1        # Nuremberg
  myapp-infra:sshKeyName: deploy-key
```

### Code Pulumi

Le point d'entrée orchestre la creation des ressources. On remarque
que c'est du TypeScript classique avec des imports, des fonctions, etc.

```typescript
// src/index.ts

import * as pulumi from "@pulumi/pulumi";
import { createNetwork } from "./network";
import { createFirewall } from "./firewall";
import { createServer } from "./server";

// Lire les variables de la stack active
const config = new pulumi.Config();
const environment = config.require("environment");

// Creer les ressources dans l'ordre
const network = createNetwork(environment);
const firewall = createFirewall(environment);
const server = createServer({
    environment,
    network,
    firewall,
    serverType: config.get("serverType") || "cx22",
    location: config.get("location") || "nbg1",
    sshKeyName: config.require("sshKeyName"),
});

// Les "exports" rendent ces valeurs accessibles apres le deploiement.
// On pourra les lire avec "pulumi stack output serverIp"
// et les utiliser dans l'inventaire Ansible.
export const serverIp = server.ipv4Address;
export const serverName = server.name;
export const networkId = network.id;
```

Le réseau privé permet aux serveurs de communiquer entre eux
sans passer par Internet (plus sécurisé, plus rapide).

```typescript
// src/network.ts

import * as hcloud from "@pulumi/hcloud";

export function createNetwork(environment: string) {
    // Creer un reseau prive avec une plage IP
    const network = new hcloud.Network(`${environment}-network`, {
        ipRange: "10.0.0.0/16",
        labels: {
            environment,
            managed_by: "pulumi",  // Label pour identifier les ressources gerees par IaC
        },
    });

    // Creer un sous-reseau dans ce reseau
    const subnet = new hcloud.NetworkSubnet(`${environment}-subnet`, {
        networkId: network.id.apply((id) => parseInt(id)),
        type: "cloud",
        networkZone: "eu-central",
        ipRange: "10.0.1.0/24",
    });

    return network;
}
```

Le firewall definit quels ports sont accessibles depuis Internet.
C'est l'equivalent cloud du UFW qu'on configure avec Ansible,
mais gere au niveau du provider.

```typescript
// src/firewall.ts

import * as hcloud from "@pulumi/hcloud";

export function createFirewall(environment: string) {
    return new hcloud.Firewall(`${environment}-firewall`, {
        labels: {
            environment,
            managed_by: "pulumi",
        },
        rules: [
            {
                direction: "in",
                protocol: "tcp",
                port: "22",
                sourceIps: ["0.0.0.0/0", "::/0"],  // Ouvert a tous (IPv4 + IPv6)
                description: "SSH",
            },
            {
                direction: "in",
                protocol: "tcp",
                port: "80",
                sourceIps: ["0.0.0.0/0", "::/0"],
                description: "HTTP",
            },
            {
                direction: "in",
                protocol: "tcp",
                port: "443",
                sourceIps: ["0.0.0.0/0", "::/0"],
                description: "HTTPS",
            },
            {
                direction: "in",
                protocol: "tcp",
                port: "6443",
                sourceIps: ["0.0.0.0/0", "::/0"],
                description: "Kubernetes API",
            },
        ],
    });
}
```

Le serveur est la ressource principale. `cloud-init` permet d'executer
des commandes au premier demarrage du serveur (avant meme qu'Ansible se connecte).

```typescript
// src/server.ts

import * as hcloud from "@pulumi/hcloud";
import * as pulumi from "@pulumi/pulumi";

interface ServerConfig {
    environment: string;
    network: hcloud.Network;
    firewall: hcloud.Firewall;
    serverType: string;
    location: string;
    sshKeyName: string;
}

export function createServer(config: ServerConfig) {
    // Recuperer une cle SSH deja enregistree chez Hetzner
    const sshKey = hcloud.getSshKey({
        name: config.sshKeyName,
    });

    // Cloud-init : script execute au premier boot du serveur
    const cloudInit = `#cloud-config
packages:
  - curl
  - git
  - htop

runcmd:
  - echo "Server provisioned by Pulumi" > /etc/motd
`;

    const server = new hcloud.Server(`${config.environment}-app`, {
        serverType: config.serverType,
        image: "ubuntu-24.04",
        location: config.location,
        sshKeys: [sshKey.then((k) => k.name)],
        firewallIds: [config.firewall.id.apply((id) => parseInt(id))],
        labels: {
            environment: config.environment,
            role: "app",
            managed_by: "pulumi",
        },
        userData: cloudInit,
    });

    // Rattacher le serveur au reseau prive
    new hcloud.ServerNetwork(`${config.environment}-app-network`, {
        serverId: server.id.apply((id) => parseInt(id)),
        networkId: config.network.id.apply((id) => parseInt(id)),
        ip: "10.0.1.10",
    });

    return server;
}
```

### Commandes Pulumi

```bash
# Prévisualiser les changements (comme un "dry-run")
# Montre ce qui serait cree/modifie/supprime
pulumi preview

# Appliquer les changements (cree reellement les ressources)
pulumi up

# Lire une valeur exportee (ex: l'IP du serveur cree)
pulumi stack output serverIp

# Voir l'etat de la stack
pulumi stack

# Detruire toute l'infrastructure (supprime les serveurs !)
pulumi destroy

# Exporter l'etat (backup)
pulumi stack export > state-backup.json
```

---

## 5.4 Inventaire dynamique Ansible

### Le concept

Un inventaire statique (fichier YAML avec des IPs en dur) fonctionne bien
quand on a un seul serveur. Mais si l'infrastructure est créée dynamiquement
par Pulumi, les IPs changent à chaque `pulumi up`.

Un **inventaire dynamique** est un script qui interroge Pulumi (ou une API cloud)
pour générer automatiquement la liste des serveurs. Ansible l'appelle avant
chaque execution pour avoir les adresses à jour.

> **Pour Hostinger** : comme on a un seul VPS avec une IP fixe, l'inventaire
> statique suffit. L'inventaire dynamique est présente ici pour comprendre
> le concept et l'utiliser si tu migres vers Hetzner ou un setup multi-serveurs.

### Script d'inventaire

Ce script Python lit les outputs de Pulumi et les formate dans le format
JSON attendu par Ansible.

```python
#!/usr/bin/env python3
# infra/ansible/inventory/pulumi_inventory.py

import json
import subprocess
import sys

def get_pulumi_outputs():
    """Recupere les outputs Pulumi (IP, nom du serveur, etc.)"""
    try:
        result = subprocess.run(
            ["pulumi", "stack", "output", "--json"],
            cwd="../pulumi",
            capture_output=True,
            text=True,
            check=True
        )
        return json.loads(result.stdout)
    except subprocess.CalledProcessError:
        return {}

def main():
    outputs = get_pulumi_outputs()

    # Format attendu par Ansible
    inventory = {
        "_meta": {
            "hostvars": {}
        },
        "all": {
            "children": ["webservers"]
        },
        "webservers": {
            "hosts": []
        }
    }

    if "serverIp" in outputs and "serverName" in outputs:
        server_name = outputs["serverName"]
        server_ip = outputs["serverIp"]

        inventory["webservers"]["hosts"].append(server_name)
        inventory["_meta"]["hostvars"][server_name] = {
            "ansible_host": server_ip,
            "ansible_user": "root",
            "ansible_python_interpreter": "/usr/bin/python3"
        }

    print(json.dumps(inventory, indent=2))

if __name__ == "__main__":
    # Ansible appelle le script avec --list pour obtenir l'inventaire
    if len(sys.argv) == 2 and sys.argv[1] == "--list":
        main()
    elif len(sys.argv) == 2 and sys.argv[1] == "--host":
        print("{}")
    else:
        print("Usage: pulumi_inventory.py --list|--host <hostname>")
        sys.exit(1)
```

```bash
# Rendre executable
chmod +x infra/ansible/inventory/pulumi_inventory.py

# Tester (doit afficher du JSON)
./infra/ansible/inventory/pulumi_inventory.py --list

# Utiliser avec Ansible (le -i pointe vers le script au lieu du fichier YAML)
ansible-playbook -i inventory/pulumi_inventory.py playbooks/site.yml
```

---

## 5.5 Ansible avance

### Role K3s

K3s est une distribution légère de Kubernetes, idéale pour un VPS.
Ce role l'installe et récupère le kubeconfig pour pouvoir piloter
le cluster depuis notre machine locale.

**Ce que fait le script d'installation K3s** :

- Téléchargé et installe le binaire K3s
- Cree un service systemd pour le démarrage automatique
- Génère les certificats TLS pour l'API Kubernetes
- Configure le stockage local et le réseau

**Les options `--disable`** :

- `--disable traefik` : on n'utilise pas le reverse proxy integre
  (on le remplacera par nginx-ingress)
- `--disable servicelb` : on n'a pas besoin du load balancer integre
  sur un single-node

```yaml
# infra/ansible/roles/k3s/tasks/main.yml

---
- name: Check if k3s is installed
  stat:
    path: /usr/local/bin/k3s
  register: k3s_binary

- name: Download k3s installer
  get_url:
    url: https://get.k3s.io
    dest: /tmp/k3s-install.sh
    mode: '0755'
  when: not k3s_binary.stat.exists

- name: Install k3s server
  shell: /tmp/k3s-install.sh
  environment:
    INSTALL_K3S_EXEC: >-
      server
      --disable traefik
      --disable servicelb
      --write-kubeconfig-mode 644
      --tls-san {{ ansible_host }}
      --tls-san {{ k3s_external_ip | default(ansible_host) }}
  args:
    # "creates" = n'exécute la commande que si ce fichier n'existe pas encore
    # C'est le mécanisme d'idempotence : si k3s est deja installe, on ne refait rien
    creates: /usr/local/bin/k3s
  when: k3s_role == 'server'

# On attend que K3s soit prêt (les pods système peuvent mettre un moment à démarrer).
- name: Wait for k3s to be ready
  command: k3s kubectl get nodes
  register: k3s_ready
  retries: 30
  delay: 10
  until: k3s_ready.rc == 0
  when: k3s_role == 'server'

- name: Get node token
  slurp:
    src: /var/lib/rancher/k3s/server/node-token
  register: k3s_token
  when: k3s_role == 'server'

# On récupère le kubeconfig pour pouvoir utiliser kubectl depuis notre machine locale
- name: Get kubeconfig
  slurp:
    src: /etc/rancher/k3s/k3s.yaml
  register: k3s_kubeconfig
  when: k3s_role == 'server'

# On sauvegarde le kubeconfig en local en remplaçant 127.0.0.1
# par l'IP publique du serveur (sinon kubectl essaierait de se
# connecter a localhost au lieu du VPS)
- name: Save kubeconfig locally
  copy:
    content: "{{ k3s_kubeconfig.content | b64decode | replace('127.0.0.1', ansible_host) }}"
    dest: "{{ playbook_dir }}/../kubeconfig-{{ inventory_hostname }}.yaml"
  delegate_to: localhost
  become: no
  when: k3s_role == 'server'
```

```yaml
# infra/ansible/roles/k3s/defaults/main.yml

---
k3s_role: server
k3s_version: v1.29.0+k3s1
k3s_external_ip: "{{ ansible_host }}"
```

### Role pour installer Helm et composants K8s

Ce role installe Helm (le gestionnaire de paquets Kubernetes) et les
composants de base dont on aura besoin :

- **nginx-ingress** : le reverse proxy qui route le trafic vers les bons pods
- **cert-manager** : gere automatiquement les certificats Let's Encrypt

```yaml
# infra/ansible/roles/k3s-addons/tasks/main.yml

---
- name: Install Helm
  shell: |
    curl https://raw.githubusercontent.com/helm/helm/main/scripts/get-helm-3 | bash
  args:
    creates: /usr/local/bin/helm

# On ajoute les depots Helm (comme des depots APT, mais pour Kubernetes)
- name: Add Helm repositories
  kubernetes.core.helm_repository:
    name: "{{ item.name }}"
    repo_url: "{{ item.url }}"
  loop:
    - { name: "ingress-nginx", url: "https://kubernetes.github.io/ingress-nginx" }
    - { name: "jetstack", url: "https://charts.jetstack.io" }
    - { name: "prometheus-community", url: "https://prometheus-community.github.io/helm-charts" }
    - { name: "grafana", url: "https://grafana.github.io/helm-charts" }
  environment:
    KUBECONFIG: /etc/rancher/k3s/k3s.yaml

- name: Install nginx-ingress
  kubernetes.core.helm:
    name: ingress-nginx
    chart_ref: ingress-nginx/ingress-nginx
    release_namespace: ingress-nginx
    create_namespace: yes
    values:
      controller:
        service:
          type: LoadBalancer
        publishService:
          enabled: true
  environment:
    KUBECONFIG: /etc/rancher/k3s/k3s.yaml

# Les CRDs (Custom Resource Definitions) ajoutent de nouveaux types
# de ressources a Kubernetes. Cert-manager a besoin des siens
# (Certificate, ClusterIssuer, etc.)
- name: Install cert-manager CRDs
  command: >
    kubectl apply -f
    https://github.com/cert-manager/cert-manager/releases/latest/download/cert-manager.crds.yaml
  environment:
    KUBECONFIG: /etc/rancher/k3s/k3s.yaml

- name: Install cert-manager
  kubernetes.core.helm:
    name: cert-manager
    chart_ref: jetstack/cert-manager
    release_namespace: cert-manager
    create_namespace: yes
    values:
      installCRDs: false  # Deja installes a l'etape precedente
  environment:
    KUBECONFIG: /etc/rancher/k3s/k3s.yaml
```

### Tests avec Molecule

Molecule est un framework de test pour les roles Ansible.
Il va créer un conteneur Docker, execute le role dedans, puis verifie
que tout s'est bien passé. C'est l'équivalent des tests unitaires pour l'infra.

> **En pratique** : tester le role K3s dans Docker est limite (K3s a besoin
> de privileged mode et systemd). C'est plus utile pour les roles simples
> (common, security, docker).

```yaml
# infra/ansible/roles/k3s/molecule/default/molecule.yml

---
dependency:
  name: galaxy
driver:
  name: docker
platforms:
  - name: instance
    image: geerlingguy/docker-ubuntu2404-ansible
    pre_build_image: true
    privileged: true
    volumes:
      - /sys/fs/cgroup:/sys/fs/cgroup:rw
    command: /lib/systemd/systemd
provisioner:
  name: ansible
verifier:
  name: ansible
```

```yaml
# infra/ansible/roles/k3s/molecule/default/converge.yml
# Ce fichier décrit ce qu'on teste (= on applique le role)

---
- name: Converge
  hosts: all
  become: yes
  vars:
    k3s_role: server
  roles:
    - role: k3s
```

```yaml
# infra/ansible/roles/k3s/molecule/default/verify.yml
# Ce fichier verifie que le role a bien fonctionne

---
- name: Verify
  hosts: all
  become: yes
  tasks:
    - name: Check k3s is installed
      command: k3s --version
      register: k3s_version
      changed_when: false

    - name: Check k3s is running
      command: systemctl is-active k3s
      register: k3s_status
      changed_when: false

    - name: Verify k3s status
      assert:
        that:
          - k3s_status.stdout == "active"
```

```bash
# Installer Molecule
pip install molecule molecule-docker ansible-lint

# Lancer les tests (cree le conteneur, applique le role, verifie, nettoie)
cd infra/ansible
molecule test -s default
```

---

## 5.6 Workflow complet

### Script de provisioning

Ce script enchaine toutes les etapes : creation de l'infra avec Pulumi,
puis configuration avec Ansible. C'est le "one-click deploy" de l'infra.

> **Pour Hostinger** : comme le VPS est cree manuellement, on saute l'etape Pulumi.
> Le script commence directement a l'etape Ansible.

```bash
#!/bin/bash
# infra/provision.sh

set -e  # Arrete le script a la premiere erreur

ENVIRONMENT="${1:-production}"

echo "=== Provisioning infrastructure for $ENVIRONMENT ==="

# --- Etape Pulumi (uniquement si provider avec API) ---
# echo ">>> Running Pulumi..."
# cd pulumi
# pulumi stack select $ENVIRONMENT
# pulumi up --yes
# SERVER_IP=$(pulumi stack output serverIp)

# --- Pour Hostinger : IP en dur ou depuis l'inventaire ---
SERVER_IP="<IP_DU_VPS_HOSTINGER>"
echo "Server IP: $SERVER_IP"

# Attendre que le serveur soit accessible en SSH
echo ">>> Waiting for server to be ready..."
until ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no root@$SERVER_IP echo "ready" 2>/dev/null; do
  echo "Waiting..."
  sleep 10
done

# Bootstrap du serveur (securite, Docker)
echo ">>> Running Ansible bootstrap..."
cd ansible
ansible-playbook \
  -i inventory/production.yml \
  playbooks/bootstrap.yml \
  -u root \
  --ask-vault-pass

# Installation de K3s
echo ">>> Installing K3s..."
ansible-playbook \
  -i inventory/production.yml \
  playbooks/k3s.yml \
  --ask-vault-pass

# Verifier le cluster
echo ">>> Kubeconfig saved"
export KUBECONFIG="$(pwd)/kubeconfig-app-prod.yaml"

echo ">>> Verifying cluster..."
kubectl get nodes
kubectl get pods -A

echo "=== Provisioning complete ==="
echo "Use: export KUBECONFIG=$(pwd)/kubeconfig-app-prod.yaml"
```

### Playbook K3s complet

```yaml
# infra/ansible/playbooks/k3s.yml

---
- name: Install K3s cluster
  hosts: webservers
  become: yes

  vars:
    k3s_role: server

  pre_tasks:
    - name: Update apt cache
      apt:
        update_cache: yes
        cache_valid_time: 3600

  roles:
    - k3s
    - k3s-addons

  post_tasks:
    - name: Display cluster info
      debug:
        msg: |
          K3s cluster is ready!
          Kubeconfig saved to: kubeconfig-{{ inventory_hostname }}.yaml

          To use:
          export KUBECONFIG={{ playbook_dir }}/../kubeconfig-{{ inventory_hostname }}.yaml
          kubectl get nodes
```

---

## 5.7 Gestion du state Pulumi

Le "state" est l'état de l'infrastructure telle que Pulumi le connait.
C'est un fichier JSON qui dit "le serveur X existe avec l'IP Y".
Sans cet état, Pulumi ne sait pas ce qui existe deja et recréerait tout.

> **Analogie** : le state est comme un inventaire de ce que Pulumi a créé.
> Si on le perd, Pulumi "oublie" les ressources existantes et il faut
> les reimporter manuellement.

### Backend local (défaut)

```bash
# State stocke dans ~/.pulumi sur ta machine
# Simple mais risque : si tu perds ta machine, tu perds le state
pulumi login --local
```

### Backend S3 compatible (recommande pour equipe)

```bash
# Stocker le state dans un bucket S3 ou compatible
# (Minio, Scaleway Object Storage, etc.)
pulumi login s3://my-pulumi-state?region=eu-west-1

# Avec un endpoint custom (ex: Scaleway)
pulumi login 's3://my-bucket?endpoint=https://s3.fr-par.scw.cloud&region=fr-par'
```

### Backend Pulumi Cloud (gratuit pour usage personnel)

```bash
# Pulumi Cloud gere le state pour toi (gratuit pour un seul utilisateur)
pulumi login
# Ouvre le navigateur pour l'authentification
```

---

## Checklist de fin de phase

- [ ] Comprendre la difference entre provisioning et configuration
- [ ] Comprendre les principes d'idempotence et de declaratif
- [ ] Pulumi : comprendre le concept (meme si non utilise avec Hostinger)
- [ ] Ansible avance : role K3s fonctionnel
- [ ] Role k3s-addons : nginx-ingress et cert-manager installes
- [ ] Kubeconfig recupere en local (`kubectl get nodes` fonctionne)
- [ ] Script de provisioning complet et teste
- [ ] (Optionnel) Inventaire dynamique Ansible avec Pulumi
- [ ] (Optionnel) Tests Molecule pour les roles Ansible
- [ ] (Optionnel) State Pulumi sauvegarde de maniere securisee
