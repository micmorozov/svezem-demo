#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 43 - Перевозка деревьев

Transp -> AnyWord<kwtype="утилизировать"> | AnyWord<kwtype="перевезти">;
What -> "дерево" | "ветка" | "порубочный";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_43);
