# AI-Powered Ransomware Detection Platform

An intelligent cybersecurity platform designed to detect suspicious and malicious activities using Sysmon logs, Machine Learning, and SOAR automation.

## Features

* Real-time Sysmon log monitoring
* AI-based anomaly detection
* FastAPI secure REST API
* Risk scoring and alert generation
* Interactive security dashboard
* Automated response with Shuffle SOAR
* Centralized MySQL logging

## Technologies

* Python
* FastAPI
* Scikit-learn
* MySQL
* PHP / JavaScript / CSS
* Sysmon
* Shuffle SOAR

## Machine Learning

The project combines:

* **Isolation Forest** for anomaly detection
* **Logistic Regression** for event classification

## Monitored Sysmon Events

* Process Creation
* Network Connections
* File Creation
* Registry Modifications
* DLL Loading

## Project Architecture

Sysmon → Python Agent → FastAPI → Machine Learning → MySQL → Dashboard → Shuffle SOAR

## Installation

```bash
git clone https://github.com/Amenibensahboun/pfe-project.git
```

Configure:

* Sysmon
* MySQL
* FastAPI server
* Shuffle workflows

## Objective

Improve ransomware detection and automate incident response using Artificial Intelligence and SOAR technologies.
