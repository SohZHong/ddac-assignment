terraform {  
  required_providers {  
    aws = {  
      source  = "hashicorp/aws"  
      version = "~> 5.0"  
    }  
    tls = {  
      source  = "hashicorp/tls"  
      version = "~> 4.0"  
    }  
    local = {  
      source  = "hashicorp/local"  
      version = "~> 2.0"  
    }  
  }  
}  
  
# Configure the AWS Provider  
provider "aws" {  
  region = "us-east-1"  
}  
  
# TODO: Create an S3 bucket  

  
# Random string for unique bucket naming  
resource "random_string" "bucket_suffix" {  
  length  = 8  
  special = false  
  upper   = false  
}  
  
# Data source to get the latest Amazon Linux 2 AMI  
data "aws_ami" "amazon_linux" {  
  most_recent = true  
  owners      = ["amazon"]  
  
  filter {  
    name   = "name"  
    values = ["amzn2-ami-hvm-*-x86_64-gp2"]  
  }  
  
  filter {  
    name   = "virtualization-type"  
    values = ["hvm"]  
  }  
}  
  
# Generate a random SSH key pair using TLS provider  
resource "tls_private_key" "example" {  
  algorithm = "RSA"  
  rsa_bits  = 2048  
}  
  
# Create AWS key pair from generated key  
resource "aws_key_pair" "deployer" {  
  key_name   = "ddac-key"  
  public_key = tls_private_key.example.public_key_openssh  
}  
  
# Save private key locally (optional)  
resource "local_file" "private_key" {  
  content  = tls_private_key.example.private_key_pem  
  filename = "${path.module}/aws-key.pem"  
  file_permission = "0600"  
}

# Create security group for SSH access  
resource "aws_security_group" "ssh_access" {  
  name_prefix = "ssh-access"  
  description = "Allow SSH inbound traffic"  
  
  ingress {  
    description = "SSH"  
    from_port   = 22  
    to_port     = 22  
    protocol    = "tcp"  
    cidr_blocks = ["0.0.0.0/0"]  # Consider restricting to your IP for better security  
  }  
  
  egress {  
    from_port   = 0  
    to_port     = 0  
    protocol    = "-1"  
    cidr_blocks = ["0.0.0.0/0"]  
  }  
  
  tags = {  
    Name = "allow_ssh"  
  }  
}  

  
# Create an EC2 instance  
resource "aws_instance" "web" {  
  ami           = data.aws_ami.amazon_linux.id  
  instance_type = "t3.micro"  
  key_name      = aws_key_pair.deployer.key_name
  vpc_security_group_ids = [aws_security_group.ssh_access.id]  # Add this line  

  tags = {  
    Name = "HelloWorld"  
  }  
}  
  
# Create RDS subnet group  
resource "aws_db_subnet_group" "default" {  
  name       = "main"  
  subnet_ids = [aws_subnet.main.id, aws_subnet.secondary.id]  
  
  tags = {  
    Name = "My DB subnet group"  
  }  
}  
  
# Create VPC for RDS (RDS requires subnet group)  
resource "aws_vpc" "main" {  
  cidr_block           = "10.0.0.0/16"  
  enable_dns_hostnames = true  
  enable_dns_support   = true  
  
  tags = {  
    Name = "main"  
  }  
}  
  
# Create subnets for RDS  
resource "aws_subnet" "main" {  
  vpc_id            = aws_vpc.main.id  
  cidr_block        = "10.0.1.0/24"  
  availability_zone = "us-east-1a"  
  
  tags = {  
    Name = "Main"  
  }  
}  
  
resource "aws_subnet" "secondary" {  
  vpc_id            = aws_vpc.main.id  
  cidr_block        = "10.0.2.0/24"  
  availability_zone = "us-east-1b"  
  
  tags = {  
    Name = "Secondary"  
  }  
}  
  
# Create RDS instance  
resource "aws_db_instance" "default" {  
  allocated_storage    = 20  
  storage_type         = "gp2"  
  engine               = "mysql"  
  engine_version       = "8.0"  
  instance_class       = "db.t3.micro"  
  identifier           = "mydb"  
  db_name              = "mydb"  
  username             = "admin"  
  password             = "password123!"  
  parameter_group_name = "default.mysql8.0"  
  db_subnet_group_name = aws_db_subnet_group.default.name  
  skip_final_snapshot  = true  
  
  tags = {  
    Name = "MyRDS"  
  }  
}