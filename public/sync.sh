#!/bin/bash

scp -i /Users/enrico/Desktop/amabile-magento-prod.pem $1 ubuntu@ec2-18-102-166-137.eu-south-1.compute.amazonaws.com:/home/ubuntu
