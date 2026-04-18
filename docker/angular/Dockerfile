FROM node:22-alpine

WORKDIR /app

COPY M_Skin_Welness_FE/package*.json ./
RUN npm install

COPY M_Skin_Welness_FE/ .

EXPOSE 4200

CMD ["npx", "ng", "serve", "--host", "0.0.0.0", "--poll", "2000"]
