#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 39 - Вывоз ванн

/*
Вывезти 2|две старые ванны
*/

Transp -> AnyWord<kwtype="утилизировать">;
What -> "ванна" | "душевая" "кабина";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_39);