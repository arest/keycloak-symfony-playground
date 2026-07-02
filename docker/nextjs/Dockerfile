FROM node:22-alpine

WORKDIR /app

# Copy package files and install dependencies
COPY ./nextjs-app/package.json ./nextjs-app/package-lock.json ./
RUN npm ci

# Source code is mounted as a bind mount for hot-reload in dev mode
CMD ["npm", "run", "dev"]
