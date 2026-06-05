FROM node:22-alpine

RUN corepack enable && corepack prepare pnpm@11.1.3 --activate

WORKDIR /app

COPY M_Skin_Welness_FE/package.json M_Skin_Welness_FE/pnpm-lock.yaml M_Skin_Welness_FE/pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile

COPY M_Skin_Welness_FE/ .

EXPOSE 4200

CMD ["pnpm", "exec", "ng", "serve", "--host", "0.0.0.0", "--poll", "2000"]
